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

use Gally\Catalog\Entity\LocalizedCatalog;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Job\Entity\Job;
use Gally\Job\Service\JobImportInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractCsvImport implements JobImportInterface
{
    public const CODE_SEPARATOR = ';';

    /**
     * @var LocalizedCatalog[]
     */
    private array $localizedCatalog = [];

    public function __construct(
        private TranslatorInterface $translator,
        private array $csvHeadear
    ) {
    }

    // TODO: Déplacer les traductios dans job.

    public function validateImportFile(Job $job): void
    {
        $this->currentJob = $job;
        $filePath = $job->getImportFile()->contentUrl;

        if (!$filePath || !file_exists($filePath)) {
            throw new \InvalidArgumentException(
                $this->translator->trans('import.error.file_not_found', [], 'gally_job')
            );
        }

        if (!is_readable($filePath)) {
            throw new \InvalidArgumentException(
                $this->translator->trans('import.error.file_not_readable', [], 'gally_job')
            );
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \InvalidArgumentException(
                $this->translator->trans('import.error.cannot_open_file', [], 'gally_job')
            );
        }

        try {
            // Validate CSV headers
            $headers = fgetcsv($handle);
            if (!$headers || array_diff($this->csvHeadear, $headers)) {
                throw new \InvalidArgumentException(
                    $this->translator->trans(
                        'thesaurus.import.error.invalid_headers',
                        ['expected' => implode(', ', $this->csvHeadear)],
                        'gally_thesaurus'
                    )
                );
            }

            $lineNumber = 1;
            while (($data = fgetcsv($handle)) !== false) {
                $lineNumber++;
                if (count($data) !== count($headers)) {
                    throw new \InvalidArgumentException(
                        $this->translator->trans(
                            'import.error.column_count_mismatch',
                            ['line' => $lineNumber, 'expected' => count($headers), 'actual' => count($data)],
                            'gally_job'
                        )
                    );
                }

                $associativeData = array_combine($headers, $data);
                $this->validateCsvLine($associativeData, $lineNumber);
            }

            $this->logInfo('thesaurus.import.validation.completed');
        } finally {
            fclose($handle);
        }
    }

    protected function parseScopeCodes(string $scopeCodes): array
    {
        $codes = explode(self::CODE_SEPARATOR, $scopeCodes);

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
        return in_array(strtolower($value), ['1', 'true'], true);
    }

    protected function parseTerms(string $terms, string $type): array
    {
        if ($type === 'synonym') {
            $groups = explode(';', $terms);
            $synonymGroups = [];

            foreach ($groups as $group) {
                if (strpos($group, ':') !== false) {
                    [$groupName, $groupTerms] = explode(':', $group, 2);
                    $synonymGroups[] = [
                        'name' => trim($groupName),
                        'terms' => array_map('trim', explode(',', $groupTerms))
                    ];
                } else {
                    $synonymGroups[] = [
                        'terms' => array_map('trim', explode(',', $group))
                    ];
                }
            }

            return $synonymGroups;
        } else {
            $expansions = [];
            $expansionGroups = explode(';', $terms);

            foreach ($expansionGroups as $expansionGroup) {
                if (strpos($expansionGroup, ':') !== false) {
                    [$referenceTerm, $expansionTerms] = explode(':', $expansionGroup, 2);
                    $expansions[] = [
                        'reference_term' => trim($referenceTerm),
                        'expansion_terms' => array_map('trim', explode(',', $expansionTerms))
                    ];
                }
            }

            return $expansions;
        }
    }

    /**
     * @return LocalizedCatalog[]
     */
    public function getLocalizedCatalogs(LocalizedCatalogRepository $localizedCatalogRepository): array
    {
        if (empty($this->localizedCatalog)) {
            $localizedCatalogs = $localizedCatalogRepository->findAll();
            foreach ($localizedCatalogs as $localizedCatalog) {
                $this->localizedCatalog[$localizedCatalog->getCode()] = $localizedCatalog;
            }
        }

        return $this->localizedCatalog;
    }

    abstract protected function validateCsvLine(array $data, int $lineNumber): void;

    abstract protected function logInfo(string $message, array $context = []): void;

    abstract protected function logError(string $message, array $context = []): void;
}
