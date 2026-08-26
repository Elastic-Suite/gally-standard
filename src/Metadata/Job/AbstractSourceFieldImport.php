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

namespace Gally\Metadata\Job;

use Gally\Doctrine\Service\EntityManagerFactory;
use Gally\Job\Exception\JobException;
use Gally\Job\Service\Csv\AbstractCsvImport;
use Gally\Job\Service\JobManager;
use Gally\Metadata\Entity\SourceField;
use Gally\Metadata\Entity\SourceField\SearchAnalyzer;
use Gally\Metadata\Repository\SourceFieldRepository;
use Gally\Metadata\Validator\SourceFieldDataValidator;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractSourceFieldImport extends AbstractCsvImport
{
    public const JOB_PROFILE = '';

    public const CSV_HEADERS = [];

    public const METADATA_ENTITY = '';

    public const BASE_CSV_HEADERS = [
        'code',
        'weight',
        'is_searchable',
        'is_filterable',
        'is_sortable',
        'is_spellchecked',
        'is_used_for_rules',
        'is_used_in_autocomplete',
        'is_spannable',
        'analyzer',
    ];

    /**
     * Mapping from CSV column names to camelCase entity property names.
     * The 'analyzer' CSV column is a special case mapping to 'defaultSearchAnalyzer'.
     */
    private const CSV_TO_PROPERTY_OVERRIDES = [
        'analyzer' => 'defaultSearchAnalyzer',
    ];

    protected SourceFieldRepository $sourceFieldRepository;

    private array $systemUpdatableCsvFields;

    /** Codes of system source fields whose restricted columns were ignored, grouped into one log line at the end of validation. */
    private array $systemFieldWarnings = [];

    private const BOOLEAN_FIELDS = [
        'is_searchable',
        'is_filterable',
        'is_sortable',
        'is_spellchecked',
        'is_used_for_rules',
        'is_used_in_autocomplete',
        'is_spannable',
    ];

    /**
     * Core columns that must carry a value. A blank cell is reported and the file rejected, rather
     * than silently skipped: every one of these is always filled by the export.
     */
    private const REQUIRED_CSV_FIELDS = [
        'weight',
        ...self::BOOLEAN_FIELDS,
        'analyzer',
    ];

    protected array $actualCsvHeader;

    public function __construct(
        protected JobManager $jobManager,
        protected EntityManagerFactory $entityManagerFactory,
        protected ValidatorInterface $validator,
        protected TranslatorInterface $translator,
        protected SourceFieldDataValidator $sourceFieldDataValidator,
        private int $batchSize = 100000,
    ) {
        $this->actualCsvHeader = static::CSV_HEADERS;
        parent::__construct($translator, $jobManager, $entityManagerFactory, static::JOB_PROFILE, static::CSV_HEADERS);
        $this->sourceFieldRepository = $this->importEntityManager->getRepository(SourceField::class);
        $this->systemUpdatableCsvFields = $this->buildSystemUpdatableCsvFields();
    }

    /**
     * Build the list of CSV fields that can be updated on a system source field,
     * derived from SourceFieldDataValidator::getUpdatableProperties().
     */
    private function buildSystemUpdatableCsvFields(): array
    {
        $csvFields = ['code']; // code is always allowed as identifier
        // Build reverse map: property name => CSV column name
        $propertyCsvOverrides = array_flip(self::CSV_TO_PROPERTY_OVERRIDES);

        foreach ($this->sourceFieldDataValidator->getUpdatableProperties() as $property) {
            if (isset($propertyCsvOverrides[$property])) {
                $csvField = $propertyCsvOverrides[$property];
            } else {
                // Convert camelCase to snake_case
                $csvField = strtolower(preg_replace('/[A-Z]/', '_$0', $property));
            }

            if (\in_array($csvField, static::CSV_HEADERS, true)) {
                $csvFields[] = $csvField;
            }
        }

        return array_merge($csvFields, $this->getAdditionalSystemUpdatableCsvFields());
    }

    public function process(): void
    {
        // Recreate the EntityManager to ensure fresh data
        $this->importEntityManager = $this->entityManagerFactory->createIsolatedEntityManager();
        $this->sourceFieldRepository = $this->importEntityManager->getRepository(SourceField::class);
        $this->initRepositories();

        $this->isCurrentJobSet();
        $this->logInfo('sourcefield.import.started', 'gally_sourcefield', ['%job_id%' => $this->currentJob->getId()]);

        $filePath = $this->jobManager->getAbsoluteJobFilePath($this->currentJob);
        $handle = fopen($filePath, 'r');

        $this->importEntityManager->getConnection()->setNestTransactionsWithSavepoints(true);
        try {
            $this->importEntityManager->getConnection()->beginTransaction();
            $this->actualCsvHeader = fgetcsv($handle, escape: '\\');

            $lineNumber = 1;
            $updatedCount = 0;
            $errorCount = 0;

            while (($data = fgetcsv($handle, escape: '\\')) !== false) {
                ++$lineNumber;
                try {
                    $this->processSourceFieldLine($data, $lineNumber);
                    ++$updatedCount;

                    // Batch processing
                    if (0 === $updatedCount % $this->batchSize) {
                        $this->importEntityManager->flush();
                        $this->importEntityManager->clear();

                        $this->logInfo('sourcefield.import.progress', 'gally_sourcefield', [
                            '%processed%' => $updatedCount,
                            '%updated%' => $updatedCount,
                        ]);
                    }
                } catch (\Throwable $e) {
                    ++$errorCount;
                    $this->logError('sourcefield.import.line_error', 'gally_sourcefield', [
                        '%line%' => $lineNumber,
                        '%error%' => $e->getMessage(),
                    ]);
                }
            }

            if ($errorCount > 0) {
                throw new JobException($this->translator->trans('sourcefield.import.error.failed', [], 'gally_sourcefield'));
            }

            $this->importEntityManager->flush();
            $this->importEntityManager->clear();
            $this->importEntityManager->getConnection()->commit();

            $this->logInfo('sourcefield.import.completed', 'gally_sourcefield', [
                '%updated%' => $updatedCount,
                '%errors%' => $errorCount,
            ]);
        } catch (\Exception $e) {
            $this->importEntityManager->getConnection()->rollBack();
            throw $e;
        } finally {
            $this->afterProcessLines();
            fclose($handle);
        }
    }

    /**
     * Called once after every line has been processed, whether processing succeeded, failed, or
     * was rolled back. Override to flush warnings accumulated per line into a single grouped log
     * message instead of logging one line per occurrence.
     */
    protected function afterProcessLines(): void
    {
    }

    protected function validateCsvLine(array $data, int $lineNumber): bool
    {
        $errors = [];

        try {
            if (empty($data['code'])) {
                $errors[] = $this->translator->trans(
                    'sourcefield.import.error.attribute_code_empty',
                    [],
                    'gally_sourcefield'
                );
            } else {
                $existingSourceField = $this->sourceFieldRepository->findByCodeAndMetadataEntity($data['code'], static::METADATA_ENTITY);
                if (!$existingSourceField) {
                    $errors[] = $this->translator->trans(
                        'sourcefield.import.error.code_not_found',
                        ['%code%' => $data['code']],
                        'gally_sourcefield'
                    );
                } elseif ($existingSourceField->getIsSystem()) {
                    $restrictedFields = array_diff(array_keys($data), $this->systemUpdatableCsvFields);
                    $ignoredFields = array_filter($restrictedFields, fn ($field) => !empty($data[$field]));
                    if (!empty($ignoredFields)) {
                        $this->systemFieldWarnings[] = $data['code'];
                    }
                }
            }

            $missingFields = [];
            foreach (self::REQUIRED_CSV_FIELDS as $field) {
                if ('' === trim((string) ($data[$field] ?? ''))) {
                    $missingFields[] = $field;
                }
            }

            if (!empty($missingFields)) {
                $errors[] = $this->translator->trans(
                    'sourcefield.import.error.values_required',
                    ['%fields%' => implode(', ', $missingFields)],
                    'gally_sourcefield'
                );
            }

            foreach (self::BOOLEAN_FIELDS as $field) {
                if (!empty($data[$field]) && !\in_array(strtolower($data[$field]), ['0', '1', self::BOOLEAN_VALUE_TRUE, self::BOOLEAN_VALUE_FALSE], true)) {
                    $errors[] = $this->translator->trans(
                        'sourcefield.import.error.invalid_boolean',
                        ['%field%' => $field, '%value%' => $data[$field]],
                        'gally_sourcefield'
                    );
                }
            }

            if (!empty($data['weight']) && !is_numeric($data['weight'])) {
                $errors[] = $this->translator->trans(
                    'sourcefield.import.error.invalid_weight',
                    ['%value%' => $data['weight']],
                    'gally_sourcefield'
                );
            }

            if (!empty($data['analyzer']) && !\in_array($data['analyzer'], SearchAnalyzer::SEARCH_ANALYZERS, true)) {
                $errors[] = $this->translator->trans(
                    'sourcefield.import.error.invalid_analyzer',
                    ['%value%' => $data['analyzer'], '%allowed%' => implode(', ', SearchAnalyzer::SEARCH_ANALYZERS)],
                    'gally_sourcefield'
                );
            }

            $errors = array_merge($errors, $this->validateAdditionalFields($data, $lineNumber));

            if (\count($errors) > 0) {
                $this->logError('sourcefield.import.validation_errors', 'gally_sourcefield', [
                    '%line%' => $lineNumber,
                    '%errors%' => implode(', ', $errors),
                ]);
            }
        } catch (\Exception $e) {
            $this->logError('sourcefield.import.line_validation_error', 'gally_sourcefield', [
                '%line%' => $lineNumber,
                '%error%' => $e->getMessage(),
            ]);
            throw $e;
        }

        return \count($errors) < 1;
    }

    protected function afterValidateLines(): void
    {
        if (!empty($this->systemFieldWarnings)) {
            $this->logInfo('sourcefield.import.warning.system_field_ignored', 'gally_sourcefield', [
                '%codes%' => implode(', ', $this->systemFieldWarnings),
                '%allowed%' => implode(', ', array_diff($this->systemUpdatableCsvFields, ['code'])),
            ]);
        }
        $this->systemFieldWarnings = [];
    }

    protected function updateSourceFieldFromData(SourceField $sourceField, array $data): SourceField
    {
        $isSystem = $sourceField->getIsSystem();

        $csvFieldSetters = [
            'is_searchable' => fn ($v) => $sourceField->setIsSearchable($this->parseBooleanValue($v)),
            'is_filterable' => fn ($v) => $sourceField->setIsFilterable($this->parseBooleanValue($v)),
            'is_sortable' => fn ($v) => $sourceField->setIsSortable($this->parseBooleanValue($v)),
            'is_used_for_rules' => fn ($v) => $sourceField->setIsUsedForRules($this->parseBooleanValue($v)),
            'is_used_in_autocomplete' => fn ($v) => $sourceField->setIsUsedInAutocomplete($this->parseBooleanValue($v)),
            'is_spellchecked' => fn ($v) => $sourceField->setIsSpellchecked($this->parseBooleanValue($v)),
            'weight' => fn ($v) => $sourceField->setWeight((int) $v),
            'is_spannable' => fn ($v) => $sourceField->setIsSpannable($this->parseBooleanValue($v)),
            'analyzer' => fn ($v) => $sourceField->setDefaultSearchAnalyzer($v),
        ];

        // Every column handled here is in REQUIRED_CSV_FIELDS, so validateCsvLine() has already
        // rejected the file if any of them was blank. Values can be written unconditionally.
        foreach ($csvFieldSetters as $csvField => $setter) {
            if (!$isSystem || \in_array($csvField, $this->systemUpdatableCsvFields, true)) {
                $setter($data[$csvField]);
            }
        }

        return $sourceField;
    }

    protected function processSourceFieldLine(array $data, int $lineNumber): void
    {
        $associativeData = array_combine($this->actualCsvHeader, $data);
        $existingSourceField = $this->sourceFieldRepository->findByCodeAndMetadataEntity($associativeData['code'], static::METADATA_ENTITY);

        if (!$existingSourceField) {
            throw new JobException($this->translator->trans('sourcefield.import.error.code_not_found', ['%code%' => $associativeData['code']], 'gally_sourcefield'));
        }

        $this->logInfo('sourcefield.import.updating', 'gally_sourcefield', ['%code%' => $associativeData['code']]);

        $sourceField = $this->updateSourceFieldFromData($existingSourceField, $associativeData);
        $this->processAdditionalData($sourceField, $associativeData);

        $sourceFieldViolations = $this->validator->validate($sourceField);
        if (\count($sourceFieldViolations) > 0) {
            $errors = [];
            foreach ($sourceFieldViolations as $violation) {
                $errors[] = $violation->getMessage();
            }
            throw new JobException($this->translator->trans('sourcefield.import.error.validation_failed', ['%errors%' => implode(', ', $errors)], 'gally_sourcefield'));
        }
    }

    /**
     * Initialize additional repositories after EntityManager recreation.
     */
    protected function initRepositories(): void
    {
    }

    /**
     * Validate additional fields specific to the entity type.
     *
     * @return string[] Validation error messages
     */
    protected function validateAdditionalFields(array $data, int $lineNumber): array
    {
        return [];
    }

    /**
     * Process additional data specific to the entity type (e.g. facet configuration).
     */
    protected function processAdditionalData(SourceField $sourceField, array $data): void
    {
    }

    /**
     * Return additional CSV fields that are always updatable on system source fields.
     * Override in subclasses to allow fields managed by separate entities (e.g. facet configuration).
     *
     * @return string[]
     */
    protected function getAdditionalSystemUpdatableCsvFields(): array
    {
        return [];
    }
}
