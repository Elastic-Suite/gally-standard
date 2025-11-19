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
use Gally\Job\Service\Csv\AbstractCsvImport;
use Gally\Job\Service\JobManager;
use Symfony\Contracts\Translation\TranslatorInterface;

class DummyImport extends AbstractCsvImport
{
    public const JOB_PROFILE = 'dummy_import';

    public const CSV_HEADERS = [
        'id',
        'name',
    ];

    public function __construct(
        protected JobManager $jobManager,
        protected EntityManagerFactory $entityManagerFactory,
        protected TranslatorInterface $translator,
        private int $batchSize = 100000
    ) {
        parent::__construct($translator, $jobManager, $entityManagerFactory, self::JOB_PROFILE, self::CSV_HEADERS);
    }

    public function getLabel(): string
    {
        return 'Dummy import';
    }

    public function process(): void
    {
        $this->isCurrentJobSet();
        $this->logInfo('Start dummy import', 'gally_job');
        $this->logInfo('Do nothing', 'gally_job');
        $this->logInfo(\sprintf('Batch size: %s)', $this->batchSize), 'gally_job');
        $this->logInfo('End dummy import', 'gally_job');
    }

    protected function validateCsvLine(array $data, int $lineNumber): bool
    {
        return true;
    }
}
