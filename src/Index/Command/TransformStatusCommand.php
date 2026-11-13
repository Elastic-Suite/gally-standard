<?php

/**
 * DISCLAIMER.
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Gally\Index\Command;

use Gally\Catalog\Entity\LocalizedCatalog;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Index\Repository\Transform\TransformRepositoryInterface;
use Gally\Index\Service\Transform\TransformProvisionerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Reports the health of every OpenSearch Transform and, on request, fixes the unhealthy ones of a given type.
 */
#[AsCommand(name: 'gally:index:transform-status')]
class TransformStatusCommand extends Command
{
    private const HEALTHY_STATUSES = ['started', 'init'];

    /** @var TransformProvisionerInterface[] */
    private array $transformProvisioners;

    /**
     * @param iterable<TransformProvisionerInterface> $transformProvisioners
     */
    public function __construct(
        private TransformRepositoryInterface $transformRepository,
        private LocalizedCatalogRepository $localizedCatalogRepository,
        iterable $transformProvisioners,
        ?string $name = null,
    ) {
        $this->transformProvisioners = [];
        foreach ($transformProvisioners as $transformProvisioner) {
            $this->transformProvisioners[$transformProvisioner->getType()] = $transformProvisioner;
        }

        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Report the health of every OpenSearch Transform and optionally fix the unhealthy ones of a given type')
            ->addOption('start', null, InputOption::VALUE_REQUIRED, 'Transform type whose unhealthy transforms must be restarted (resumes from their last checkpoint)')
            ->addOption('recreate', null, InputOption::VALUE_REQUIRED, 'Transform type whose unhealthy transforms must be recreated from scratch (heavier: resets their checkpoint)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ui = new SymfonyStyle($input, $output);
        $startType = $input->getOption('start');
        $recreateType = $input->getOption('recreate');

        foreach (array_filter([$startType, $recreateType]) as $type) {
            if (!isset($this->transformProvisioners[$type])) {
                $ui->error(\sprintf(
                    'Unknown transform type "%s". Available types: %s.',
                    $type,
                    implode(', ', array_keys($this->transformProvisioners))
                ));

                return Command::INVALID;
            }
        }

        $transforms = $this->transformRepository->findAll();
        if (empty($transforms)) {
            $ui->writeln('No transform found.');

            return Command::SUCCESS;
        }

        $transformTypes = $this->getTransformTypes();
        $rows = [];
        $hasUnhealthyTransform = false;
        foreach ($transforms as $transform) {
            $transformId = $transform->getId();
            [$status, $failureReason, $documentsBehind] = $this->explain($transformId);
            $healthy = \in_array($status, self::HEALTHY_STATUSES, true);
            $hasUnhealthyTransform = $hasUnhealthyTransform || !$healthy;

            $rows[] = [
                $transformId,
                $transformTypes[$transformId] ?? '',
                $status,
                $healthy ? 'yes' : 'no',
                $documentsBehind,
                $failureReason ?? '',
            ];
        }

        $ui->table(['Transform', 'Type', 'Status', 'Healthy', 'Pending events', 'Failure reason'], $rows);

        if (null === $startType && null === $recreateType) {
            return $hasUnhealthyTransform ? Command::FAILURE : Command::SUCCESS;
        }

        if (null !== $startType) {
            $transformProvisioner = $this->transformProvisioners[$startType];
            foreach ($this->getUnhealthyLocalizedCatalogs($transformProvisioner) as $localizedCatalog) {
                $this->tryStart($ui, $transformProvisioner->getTransformId($localizedCatalog));
            }
        }

        if (null !== $recreateType) {
            $transformProvisioner = $this->transformProvisioners[$recreateType];
            foreach ($this->getUnhealthyLocalizedCatalogs($transformProvisioner) as $localizedCatalog) {
                $this->tryRecreate($ui, $transformProvisioner, $localizedCatalog);
            }
        }

        return Command::SUCCESS;
    }

    /**
     * @return array<string, string> Transform type indexed by transform id
     */
    private function getTransformTypes(): array
    {
        $transformTypes = [];
        foreach ($this->transformProvisioners as $type => $transformProvisioner) {
            foreach ($this->localizedCatalogRepository->findAll() as $localizedCatalog) {
                $transformTypes[$transformProvisioner->getTransformId($localizedCatalog)] = $type;
            }
        }

        return $transformTypes;
    }

    /**
     * @return LocalizedCatalog[]
     */
    private function getUnhealthyLocalizedCatalogs(TransformProvisionerInterface $transformProvisioner): array
    {
        return array_filter(
            $this->localizedCatalogRepository->findAll(),
            fn (LocalizedCatalog $localizedCatalog) => !\in_array(
                $this->explain($transformProvisioner->getTransformId($localizedCatalog))[0],
                self::HEALTHY_STATUSES,
                true
            )
        );
    }

    /**
     * @return array{0: string, 1: ?string, 2: int|string}
     */
    private function explain(string $transformId): array
    {
        try {
            $explain = $this->transformRepository->explain($transformId);
            $metadata = $explain[$transformId]['transform_metadata'] ?? [];
        } catch (\Exception $exception) {
            return ['unreachable', $exception->getMessage(), '-'];
        }

        $documentsBehind = $metadata['continuous_stats']['documents_behind'] ?? null;
        $lag = null !== $documentsBehind ? array_sum($documentsBehind) : '-';

        return [$metadata['status'] ?? 'unknown', $metadata['failure_reason'] ?? null, $lag];
    }

    private function tryStart(SymfonyStyle $ui, string $transformId): void
    {
        try {
            $this->transformRepository->start($transformId);
            $ui->writeln("Started \"{$transformId}\".");
        } catch (\Exception $exception) {
            $ui->warning("Could not start \"{$transformId}\": {$exception->getMessage()}");
        }
    }

    private function tryRecreate(SymfonyStyle $ui, TransformProvisionerInterface $transformProvisioner, LocalizedCatalog $localizedCatalog): void
    {
        $transformId = $transformProvisioner->getTransformId($localizedCatalog);

        try {
            $provisionedTransformId = $transformProvisioner->createOrUpdate($localizedCatalog);
            $ui->writeln(
                null === $provisionedTransformId
                    ? "Skipped \"{$transformId}\" (nothing to provision)."
                    : "Recreated \"{$transformId}\"."
            );
        } catch (\Exception $exception) {
            $ui->warning("Could not recreate \"{$transformId}\": {$exception->getMessage()}");
        }
    }
}
