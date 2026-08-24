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

namespace Gally\Tracker\Command;

use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Index\Repository\Transform\TransformRepositoryInterface;
use Gally\Tracker\Service\SessionTransformProvisioner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Reports the health of every OpenSearch Transform and, on request, fixes the unhealthy ones.
 */
#[AsCommand(name: 'gally:tracker:transform-status')]
class TransformStatusCommand extends Command
{
    private const HEALTHY_STATUSES = ['started', 'init'];

    public function __construct(
        private TransformRepositoryInterface $transformRepository,
        private LocalizedCatalogRepository $localizedCatalogRepository,
        private SessionTransformProvisioner $sessionTransformProvisioner,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Report the health of every OpenSearch Transform and optionally fix the unhealthy ones')
            ->addOption('start', null, InputOption::VALUE_NONE, 'Restart every unhealthy transform (resumes from its last checkpoint)')
            ->addOption('recreate', null, InputOption::VALUE_NONE, 'Recreate every unhealthy tracking_session transform from scratch (heavier: resets its checkpoint)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ui = new SymfonyStyle($input, $output);
        $start = (bool) $input->getOption('start');
        $recreate = (bool) $input->getOption('recreate');

        $transforms = $this->transformRepository->findAll();
        if (empty($transforms)) {
            $ui->writeln('No transform found.');

            return Command::SUCCESS;
        }

        $rows = [];
        $unhealthyIds = [];
        foreach ($transforms as $transform) {
            $transformId = $transform->getId();
            [$status, $failureReason, $documentsBehind] = $this->explain($transformId);
            $healthy = \in_array($status, self::HEALTHY_STATUSES, true);

            if (!$healthy) {
                $unhealthyIds[] = $transformId;
            }

            $rows[] = [
                $transformId,
                $status,
                $healthy ? 'yes' : 'no',
                $documentsBehind,
                $failureReason ?? '',
            ];
        }

        $ui->table(['Transform', 'Status', 'Healthy', 'Pending events', 'Failure reason'], $rows);

        if (!$start && !$recreate) {
            return empty($unhealthyIds) ? Command::SUCCESS : Command::FAILURE;
        }

        foreach ($unhealthyIds as $transformId) {
            if ($start) {
                $this->tryStart($ui, $transformId);
            }

            if ($recreate) {
                $this->tryRecreate($ui, $transformId);
            }
        }

        return Command::SUCCESS;
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

    private function tryRecreate(SymfonyStyle $ui, string $transformId): void
    {
        $prefix = SessionTransformProvisioner::TRANSFORM_ID_PREFIX;
        if (!str_starts_with($transformId, $prefix)) {
            $ui->warning("Don't know how to recreate \"{$transformId}\" (not a tracking_session transform), skipping.");

            return;
        }

        $catalogCode = substr($transformId, \strlen($prefix));

        try {
            $localizedCatalog = $this->localizedCatalogRepository->findByCodeOrId($catalogCode);
            $this->sessionTransformProvisioner->createOrUpdate($localizedCatalog);
            $ui->writeln("Recreated \"{$transformId}\".");
        } catch (\Exception $exception) {
            $ui->warning("Could not recreate \"{$transformId}\": {$exception->getMessage()}");
        }
    }
}
