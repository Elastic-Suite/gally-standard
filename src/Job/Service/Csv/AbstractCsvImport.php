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

use Doctrine\Common\Collections\ArrayCollection;
use Gally\Catalog\Entity\LocalizedCatalog;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Job\Entity\Job;
use Gally\Job\Service\JobImportInterface;
use Gally\Thesaurus\Entity\Thesaurus\Expansion;
use Gally\Thesaurus\Entity\Thesaurus\Synonym;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractCsvImport implements JobImportInterface
{
    public const CODE_SEPARATOR = ';';

    protected ?Job $currentJob = null;

    /**
     * @var LocalizedCatalog[]
     */
    private array $localizedCatalog = [];

    public function __construct(
        private TranslatorInterface $translator,
        private KernelInterface $kernel,
        protected array $csvHeader
    ) {
    }

    // TODO: Déplacer les traductios dans job.

    public function validateImportFile(Job $job): void
    {
        $this->currentJob = $job;
        $filePath = $this->getAbsoluteFilePath($job->getImportFile()->filePath);

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
            if (!$headers || array_diff($this->csvHeader, $headers)) {
                throw new \InvalidArgumentException(
                    $this->translator->trans(
                        'thesaurus.import.error.invalid_headers',
                        ['expected' => implode(', ', $this->csvHeader)],
                        'gally_thesaurus'
                    )
                );
            }

            $errors = [];
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
                $errors = array_merge($errors, $this->validateCsvLine($associativeData, $lineNumber));
            }

            if (\count($errors) > 0) {
                throw new \InvalidArgumentException(
                    $this->translator->trans(
                        'thesaurus.import.error.validation_failed',
                        ['line' => $lineNumber, 'errors' => implode(', ', $errors)],
                        'gally_thesaurus'
                    )
                );
            }

            $this->logInfo('thesaurus.import.validation.completed');
        } finally {
            fclose($handle);
        }
    }

    public function getAbsoluteFilePath(string $fileName): string
    {
        return $this->kernel->getProjectDir() . '/var/jobs/import/' . ltrim($fileName, '/');
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

    protected function parseTerms(string $terms, string $type): ArrayCollection
    {
        if ($type === 'synonym') {
            $groups = explode(';', $terms);
            $synonymGroups = new ArrayCollection();

            foreach ($groups as $group) {
//                if (strpos($group, ':') !== false) {
//                    [$groupName, $groupTerms] = explode(':', $group, 2);
//                    $synonymGroups->add([
//                        'name' => trim($groupName),
//                        'terms' => new ArrayCollection(array_map('trim', explode(',', $groupTerms)))
//                    ]);
//                } else {
                $synonym = new Synonym();
                $terms = array_map('trim', explode(',', $group));
                foreach ($terms as $term) {
                    $termObject = new Synonym\Term();
                    $termObject->setTerm(trim($term));
                    $synonym->addTerm($termObject);
                }
                $synonymGroups->add($synonym);
//                }
            }

            return $synonymGroups;
        } else {
            $expansions = new ArrayCollection();
            $expansionGroups = explode(';', $terms);

            foreach ($expansionGroups as $expansionGroup) {
                if (strpos($expansionGroup, ':') !== false) {
                    [$referenceTerm, $expansionTerms] = explode(':', $expansionGroup, 2);
                    $terms = array_map('trim', explode(',', $expansionTerms));
                    $expansion = new Expansion();
                    $expansion->setReferenceTerm(trim($referenceTerm));
                    foreach ($terms as $term) {
                        $termObject = new Expansion\Term();
                        $termObject->setTerm(trim($term));
                        $expansion->addTerm($termObject);
                    }
                    $expansions->add($expansion);
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
                $this->localizedCatalog[strtolower($localizedCatalog->getCode())] = $localizedCatalog;
            }
        }

        return $this->localizedCatalog;
    }

    abstract protected function validateCsvLine(array $data, int $lineNumber): array;

    abstract protected function logInfo(string $message, array $context = []): void;

    abstract protected function logError(string $message, array $context = []): void;
}
