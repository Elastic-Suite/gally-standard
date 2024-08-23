<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @package   Gally
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Gally\Index\Command;

use Gally\Index\Repository\Index\IndexRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
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
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setDescription('Delete all elasticsearch indices');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ui = new SymfonyStyle($input, $output);
        if (!$ui->confirm('Careful, all elasticsearch indices will be deleted. Do you want to continue?', !$input->isInteractive())) {
            return Command::SUCCESS;
        }

        $this->indexRepository->delete('gally*');
        $ui->writeln('Elasticsearch indices have been deleted.');

        return Command::SUCCESS;
    }
}
