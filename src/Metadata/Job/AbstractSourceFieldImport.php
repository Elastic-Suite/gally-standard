<?php

/**
 * DISCLAIMER.
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Licensed to Smile-SA. All rights reserved. No warranty, explicit or implicit, provided.
 *            Unauthorized copying of this file, via any medium, is strictly prohibited.
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
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractSourceFieldImport extends AbstractCsvImport
{
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

    protected SourceFieldRepository $sourceFieldRepository;

    private const BOOLEAN_FIELDS = [
        'is_searchable',
        'is_filterable',
        'is_sortable',
        'is_spellchecked',
        'is_used_for_rules',
        'is_used_in_autocomplete',
        'is_spannable',
    ];

    protected array $actualCsvHeader;

    public function __construct(
        protected JobManager $jobManager,
        protected EntityManagerFactory $entityManagerFactory,
        protected ValidatorInterface $validator,
        protected TranslatorInterface $translator,
        private int $batchSize = 100000,
    ) {
        $this->actualCsvHeader = static::CSV_HEADERS;
        parent::__construct($translator, $jobManager, $entityManagerFactory, static::JOB_PROFILE, static::CSV_HEADERS);
        $this->sourceFieldRepository = $this->importEntityManager->getRepository(SourceField::class);
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
                    if (($updatedCount) % $this->batchSize === 0) {
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
            fclose($handle);
        }
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
                // TODO we cannot allow to load each source field to test its existence
                $existingSourceField = $this->sourceFieldRepository->findByCodeAndMetadataEntity($data['code'], static::METADATA_ENTITY);
                if (!$existingSourceField) {
                    $errors[] = $this->translator->trans(
                        'sourcefield.import.error.code_not_found',
                        ['%code%' => $data['code']],
                        'gally_sourcefield'
                    );
                }
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

    protected function updateSourceFieldFromData(SourceField $sourceField, array $data): SourceField
    {
        $sourceField->setIsSearchable($this->parseBooleanValue($data['is_searchable']));
        $sourceField->setIsFilterable($this->parseBooleanValue($data['is_filterable']));
        $sourceField->setIsSortable($this->parseBooleanValue($data['is_sortable']));
        $sourceField->setIsSpellchecked($this->parseBooleanValue($data['is_spellchecked']));
        $sourceField->setIsUsedForRules($this->parseBooleanValue($data['is_used_for_rules']));
        $sourceField->setIsUsedInAutocomplete($this->parseBooleanValue($data['is_used_in_autocomplete']));
        $sourceField->setWeight(\intval($data['weight']));
        $sourceField->setIsSpannable($this->parseBooleanValue($data['is_spannable']));
        $sourceField->setDefaultSearchAnalyzer($data['analyzer']);

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
}
