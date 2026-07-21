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
use Gally\Search\Entity\Facet\Configuration;
use Gally\Search\Repository\Facet\ConfigurationRepository;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SourceFieldImport extends AbstractCsvImport
{
    public const JOB_PROFILE = 'sourcefield_import';

    protected SourceFieldRepository $sourceFieldRepository;
    protected ConfigurationRepository $facetConfigurationRepository;

    public const CSV_HEADERS = [
        // Source field
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
        // Facet configuration
        'display_mode', 
        'coverage_rate',
        'max_size',
        'sort_order',
        'position',
        'boolean_logic'
    ];

    private const BOOLEAN_FIELDS = [
        'is_searchable',
        'is_filterable',
        'is_sortable',
        'is_spellchecked',
        'is_used_for_rules',
        'is_used_in_autocomplete',
        'is_spannable',
    ];

    public function __construct(
        protected JobManager $jobManager,
        protected EntityManagerFactory $entityManagerFactory,
        private ValidatorInterface $validator,
        protected TranslatorInterface $translator,
        private int $batchSize = 100000,
    ) {
        parent::__construct($translator, $jobManager, $entityManagerFactory, self::JOB_PROFILE, self::CSV_HEADERS);
        $this->sourceFieldRepository = $this->importEntityManager->getRepository(SourceField::class);
        $this->facetConfigurationRepository = $this->importEntityManager->getRepository(Configuration::class);
    }

    public function getLabel(): string
    {
        return $this->translator->trans('sourcefield.import.label', [], 'gally_sourcefield');
    }

    public function process(): void
    {
        // Recreate the EntityManager to ensure fresh data
        $this->importEntityManager = $this->entityManagerFactory->createIsolatedEntityManager();
        $this->sourceFieldRepository = $this->importEntityManager->getRepository(SourceField::class);
        $this->facetConfigurationRepository = $this->importEntityManager->getRepository(Configuration::class);

        $this->isCurrentJobSet();
        $this->logInfo('sourcefield.import.started', 'gally_sourcefield', ['%job_id%' => $this->currentJob->getId()]);

        $filePath = $this->jobManager->getAbsoluteJobFilePath($this->currentJob);
        $handle = fopen($filePath, 'r');

        $this->importEntityManager->getConnection()->setNestTransactionsWithSavepoints(true);
        try {
            $this->importEntityManager->getConnection()->beginTransaction();
            // Skip headers
            fgetcsv($handle, escape: '\\');

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
                        $this->importEntityManager->clear(); // Clear memory

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
            $this->importEntityManager->clear(); // Clear memory
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
                $existingSourceField = $this->sourceFieldRepository->findByCodeAndMetadataEntity($data['code'], 'product');
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

            if (!empty($data['display_mode']) && !\in_array($data['display_mode'], Configuration::getAvailableDisplayModes(), true)) {
                $this->logInfo(json_encode([
                    $data['display_mode'],
                    Configuration::getAvailableDisplayModes()
                ]), 'gally_sourcefield');
                $errors[] = $this->translator->trans(
                    'sourcefield.import.error.invalid_display_mode',
                    ['%value%' => $data['display_mode']],
                    'gally_sourcefield'
                );
            }

            if (!empty($data['coverage_rate']) && (!is_numeric($data['coverage_rate']) || (int) $data['coverage_rate'] < 0 || (int) $data['coverage_rate'] > 100)) {
                $errors[] = $this->translator->trans(
                    'sourcefield.import.error.invalid_coverage_rate',
                    ['%value%' => $data['coverage_rate']],
                    'gally_sourcefield'
                );
            }

            if (!empty($data['max_size']) && (!is_numeric($data['max_size']) || (int) $data['max_size'] < 0)) {
                $errors[] = $this->translator->trans(
                    'sourcefield.import.error.invalid_max_size',
                    ['%value%' => $data['max_size']],
                    'gally_sourcefield'
                );
            }

            if (!empty($data['sort_order']) && !\in_array($data['sort_order'], Configuration::getAvailableSortOrder(), true)) {
                $this->logInfo(json_encode([
                    $data['display_mode'],
                    Configuration::getAvailableDisplayModes()
                ]), 'gally_sourcefield');
                $errors[] = $this->translator->trans(
                    'sourcefield.import.error.invalid_sort_order',
                    ['%value%' => $data['sort_order']],
                    'gally_sourcefield'
                );
            }

            if (isset($data['position']) && $data['position'] !== '' && !is_numeric($data['position'])) {
                $errors[] = $this->translator->trans(
                    'sourcefield.import.error.invalid_position',
                    ['%value%' => $data['position']],
                    'gally_sourcefield'
                );
            }

            if (!empty($data['boolean_logic']) && !\in_array(strtoupper($data['boolean_logic']), ['OR', 'AND'], true)) {
                $errors[] = $this->translator->trans(
                    'sourcefield.import.error.invalid_boolean_logic',
                    ['%value%' => $data['boolean_logic']],
                    'gally_sourcefield'
                );
            }

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

        // TODO: check if we can move event subscriber validation to the validator using complex validator class

        return \count($errors) < 1;
    }

    private function updateSourceFieldFromData(SourceField $sourceField, array $data): SourceField
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

        // TODO: check facet configuration default value handling (do we need to set default values)

        // TODO: what to do if the source field is not filterable but we import configuration ?
        // - If the attribute has facet config we export values regardless of the its is_filterable config
        // - Import the facet config regardless of the is filterable config
        // - How does default values is handled ? Do avoid insertion until necessary because of performance issues ?

        // TODO: check facet configuration logic loading and saving order ?

        // TODO: create many sources fields to test large batch sizes

        return $sourceField;
    }

    private function upsertFacetConfigurationFromData(SourceField $sourceField, array $data): Configuration
    {
        $facetConfig = $this->facetConfigurationRepository->findOneBySourceFieldAndDefaultCategory($sourceField);
        $this->logInfo(
            json_encode($facetConfig), 
            'gally_sourcefield', 
        );
        if (null === $facetConfig) {
            $sourceFieldReference = $this->importEntityManager->getReference(SourceField::class, $sourceField->getId());
            $facetConfig = new Configuration($sourceFieldReference, null);
            $this->importEntityManager->persist($facetConfig);
            $this->logInfo(
                'sourcefield.import.creating.default_facet_configuration', 
                'gally_sourcefield', 
                ['%code%' => $sourceField->getCode()],
            );
        } else {
            $this->logInfo(
                'sourcefield.import.updating.default_facet_configuration', 
                'gally_sourcefield', 
                ['%code%' => $sourceField->getCode()],
            );
        }

        $displayMode = $data['display_mode'] === $facetConfig->getDefaultDisplayMode() ? '' : $data['display_mode'];
        $coverageRate = (int) $data['coverage_rate'] === $facetConfig->getDefaultCoverageRate() ? null : ((int) $data['coverage_rate']);
        $maxSize = (int) $data['max_size'] === $facetConfig->getDefaultMaxSize() ? null : ((int) $data['max_size']);
        $sortOrder = $data['sort_order'] === $facetConfig->getDefaultSortOrder() ? null : ($data['sort_order']);
        $position = $data['position'] === '' ? $facetConfig->getDefaultPosition() : (int) $data['position'];
        $booleanLogic = strtoupper($data['boolean_logic']) === $facetConfig->getDefaultBooleanLogic() ? null : strtoupper($data['boolean_logic']);

        $facetConfig->setDisplayMode($displayMode);
        $facetConfig->setCoverageRate($coverageRate);
        $facetConfig->setMaxSize($maxSize);
        $facetConfig->setSortOrder($sortOrder);
        $facetConfig->setPosition($position);
        $facetConfig->setBooleanLogic($booleanLogic);

        return $facetConfig;
    }

    protected function processSourceFieldLine(array $data, int $lineNumber): void
    {
        $associativeData = array_combine(self::CSV_HEADERS, $data);
        $existingSourceField = $this->sourceFieldRepository->findByCodeAndMetadataEntity($associativeData['code'], 'product');

        if ($existingSourceField) {
            $this->logInfo('sourcefield.import.updating', 'gally_sourcefield', ['%code%' => $associativeData['code']]);
        } else {
            throw new JobException($this->translator->trans('sourcefield.import.error.code_not_found', ['%code%' => $associativeData['code']], 'gally_sourcefield'));
        }

        $sourceField = $this->updateSourceFieldFromData($existingSourceField, $associativeData);
        $facetConfiguration = $this->upsertFacetConfigurationFromData($sourceField, $associativeData);

        $sourceFieldViolations = $this->validator->validate($sourceField);
        $facetConfigurationViolations = $this->validator->validate($facetConfiguration);
        $violationCounts = \count($sourceFieldViolations) + \count($facetConfigurationViolations);

        if ($violationCounts > 0) {
            $allViolations = new \AppendIterator();
            $allViolations->append($sourceFieldViolations->getIterator());
            $allViolations->append($facetConfigurationViolations->getIterator());

            $errors = [];
            foreach ($allViolations as $violation) {
                $errors[] = $violation->getMessage();
            }
            throw new JobException($this->translator->trans('sourcefield.import.error.validation_failed', ['%errors%' => implode(', ', $errors)], 'gally_sourcefield'));
        }
    }
}