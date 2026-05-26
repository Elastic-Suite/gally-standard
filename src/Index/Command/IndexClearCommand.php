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

use Gally\Index\Repository\DataStream\DataStreamRepositoryInterface;
use Gally\Index\Repository\Index\IndexRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'gally:index:clear')]
class IndexClearCommand extends Command
{
    /**
     * IndexClearCommand constructor.
     */
    public function __construct(
        private IndexRepositoryInterface $indexRepository,
        private DataStreamRepositoryInterface $dataStreamRepository,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Delete all elasticsearch indices')
            ->addOption('with-data-streams', null, InputOption::VALUE_NONE, 'Also delete all data streams');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $withDataStreams = $input->getOption('with-data-streams');
        $ui = new SymfonyStyle($input, $output);

        $confirmMessage = $withDataStreams
            ? 'Careful, all elasticsearch indices and data streams will be deleted. Do you want to continue?'
            : 'Careful, all elasticsearch indices will be deleted. Do you want to continue?';

        if (!$ui->confirm($confirmMessage, !$input->isInteractive())) {
            return Command::SUCCESS;
        }

        $this->indexRepository->delete('gally*');
        $ui->writeln('Elasticsearch indices have been deleted.');

        if ($withDataStreams) {
            $this->dataStreamRepository->deleteAll();
            $ui->writeln('Data streams have been deleted.');
        }

        return Command::SUCCESS;
    }
}
