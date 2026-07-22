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
use Gally\Tracker\Service\SessionTransformProvisioner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Creates/updates and (re)starts the tracking_session OpenSearch Transform for a localized
 * catalog. Run this whenever tracking_session is (re)installed for that catalog -- there is no
 * automatic hook for it yet.
 */
#[AsCommand(name: 'gally:tracker:provision-session-transform')]
class ProvisionSessionTransformCommand extends Command
{
    public function __construct(
        private LocalizedCatalogRepository $localizedCatalogRepository,
        private SessionTransformProvisioner $provisioner,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Create/update and start the tracking_session OpenSearch Transform for a localized catalog')
            ->addArgument('localizedCatalog', InputArgument::REQUIRED, 'Localized catalog code');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ui = new SymfonyStyle($input, $output);
        $localizedCatalog = $this->localizedCatalogRepository->findByCodeOrId($input->getArgument('localizedCatalog'));

        $transformId = $this->provisioner->createOrUpdate($localizedCatalog);

        $ui->writeln(sprintf('Transform "%s" created/updated and started.', $transformId));

        return Command::SUCCESS;
    }
}
