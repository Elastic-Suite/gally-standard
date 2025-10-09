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

namespace Gally\Job\Service\Csv;

use Doctrine\Persistence\ObjectManager;
use Gally\Doctrine\Service\EntityManagerFactory;
use Gally\Job\Service\JobExportInterface;
use Gally\Job\Service\JobManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractCsvExport extends AbstractCsv implements JobExportInterface
{
    protected ObjectManager $exportEntityManager;

    public function __construct(
        protected TranslatorInterface $translator,
        protected JobManager $jobManager,
        protected EntityManagerFactory $entityManagerFactory,
        protected Filesystem $filesystem,
        protected string $jobProfile,
    ) {
        parent::__construct($translator, $jobManager, $jobProfile);
        $this->exportEntityManager = $this->entityManagerFactory->createIsolatedEntityManager();
    }

    protected function getThesaurusTypeLabel(string $type): string
    {
        return match ($type) {
            self::SYNONYM_TYPE => self::SYNONYM_TYPE,
            self::EXPANSION_TYPE => self::EXPANSION_TYPE,
            default => $type
        };
    }

    protected function getScopeTypeLabel(string $scopeType): string
    {
        return match ($scopeType) {
            self::SCOPE_TYPE_LOCALIZED_CATALOG => self::SCOPE_TYPE_LOCALIZED_CATALOG,
            self::SCOPE_TYPE_LOCALE => self::SCOPE_TYPE_LOCALE,
            default => $scopeType
        };
    }

    protected function formatBoolean(bool $value): string
    {
        return $value ? self::BOOLEAN_VALUE_TRUE : self::BOOLEAN_VALUE_FALSE;
    }

    protected function prepareExportFile(string $entityName): array
    {
        // Prepare export file path
        $exportDir = $this->jobManager->getJobDirectoryPath();
        $this->filesystem->mkdir($exportDir);
        $fileName = \sprintf('%s_export_%s_%d.csv', $entityName, date('Y-m-d_H-i-s'), $this->currentJob->getId());
        $filepath = $exportDir . '/' . $fileName;

        return [$filepath, $fileName];
    }
}
