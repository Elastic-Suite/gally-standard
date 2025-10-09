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

use Doctrine\ORM\EntityManager;
use Gally\Catalog\Entity\LocalizedCatalog;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Doctrine\Service\EntityManagerFactory;
use Gally\Job\Exception\JobException;
use Gally\Job\Service\JobImportInterface;
use Gally\Job\Service\JobManager;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractCsvImport extends AbstractCsv implements JobImportInterface
{
    /**
     * @var LocalizedCatalog[]
     */
    private array $localizedCatalogs = [];

    protected EntityManager $importEntityManager;

    public function __construct(
        protected TranslatorInterface $translator,
        protected JobManager $jobManager,
        protected EntityManagerFactory $entityManagerFactory,
        protected string $jobProfile,
        protected array $csvHeader,
    ) {
        parent::__construct($translator, $jobManager, $jobProfile);
        $this->importEntityManager = $this->entityManagerFactory->createIsolatedEntityManager();
    }

    public function validateImportFile(): void
    {
        $this->isCurrentJobSet();
        $filePath = $this->jobManager->getAbsoluteJobFilePath($this->currentJob);

        if (!$filePath || !file_exists($filePath)) {
            throw new JobException($this->translator->trans('import.error.file_not_found', [], 'gally_job'));
        }

        if (!is_readable($filePath)) {
            throw new JobException($this->translator->trans('import.error.file_not_readable', [], 'gally_job'));
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new JobException($this->translator->trans('import.error.cannot_open_file', [], 'gally_job'));
        }

        try {
            // Validate CSV headers
            $headers = fgetcsv($handle);
            if (!$headers || array_diff($this->csvHeader, $headers) || array_diff($headers, $this->csvHeader)) {
                throw new JobException($this->translator->trans('import.error.invalid_headers', ['%expected%' => implode(', ', $this->csvHeader)], 'gally_job'));
            }

            $errors = false;
            $lineNumber = 1;
            while (($data = fgetcsv($handle)) !== false) {
                ++$lineNumber;
                if (\count($data) !== \count($headers)) {
                    $this->logInfo(
                        'import.error.column_count_mismatch', 'gally_job', ['%line%' => $lineNumber, '%expected%' => \count($headers), '%actual%' => \count($data)]
                    );
                    $errors = true;
                    continue;
                }

                $associativeData = array_combine($headers, $data);
                $errors = !$this->validateCsvLine($associativeData, $lineNumber) || $errors;
            }

            if ($errors) {
                throw new JobException($this->translator->trans('import.error.validation_failed', [], 'gally_job'));
            }

            $this->logInfo('import.validation.completed', 'gally_job');
        } finally {
            fclose($handle);
        }
    }

    protected function parseScopeCodes(string $scopeCodes): array
    {
        $codes = explode(self::VALUE_SEPARATOR, $scopeCodes);

        $cleanCodes = [];
        foreach ($codes as $code) {
            $cleanCode = trim($code);
            if (!empty($cleanCode)) {
                $cleanCodes[$cleanCode] = $cleanCode;
            }
        }

        return $cleanCodes;
    }

    protected function parseBooleanValue(string $value): bool
    {
        return \in_array(strtolower($value), ['1', self::BOOLEAN_VALUE_TRUE], true);
    }

    /**
     * @return LocalizedCatalog[]
     */
    public function getLocalizedCatalogs(LocalizedCatalogRepository $localizedCatalogRepository): array
    {
        if (empty($this->localizedCatalogs)) {
            $localizedCatalogs = $localizedCatalogRepository->findAll();
            foreach ($localizedCatalogs as $localizedCatalog) {
                $this->localizedCatalogs[strtolower($localizedCatalog->getCode())] = $localizedCatalog;
            }
        }

        return $this->localizedCatalogs;
    }

    abstract protected function validateCsvLine(array $data, int $lineNumber): bool;
}
