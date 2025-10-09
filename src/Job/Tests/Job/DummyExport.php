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

namespace Gally\Job\Tests\Job;

use Gally\Doctrine\Service\EntityManagerFactory;
use Gally\Job\Service\Csv\AbstractCsvExport;
use Gally\Job\Service\JobManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\Translation\TranslatorInterface;

class DummyExport extends AbstractCsvExport
{
    public const JOB_PROFILE = 'dummy_export';

    public function __construct(
        protected JobManager $jobManager,
        protected EntityManagerFactory $entityManagerFactory,
        protected TranslatorInterface $translator,
        protected Filesystem $filesystem,
        private int $batchSize = 100
    ) {
        parent::__construct($translator, $jobManager, $entityManagerFactory, $filesystem, self::JOB_PROFILE);
    }

    public function getLabel(): string
    {
        return 'Dummy export';
    }

    public function process(): void
    {
        $this->isCurrentJobSet();
        $this->logInfo('Start dummy export', 'gally_job');
        $this->logInfo('Do nothing', 'gally_job');
        $this->logInfo(\sprintf('Batch size: %s', $this->batchSize), 'gally_job');
        $this->logInfo('End dummy export', 'gally_job');
    }
}
