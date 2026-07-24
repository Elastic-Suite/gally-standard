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

namespace Gally\Metadata\Job\Product;

use Gally\Doctrine\Service\EntityManagerFactory;
use Gally\Job\Exception\JobException;
use Gally\Job\Service\JobManager;
use Gally\Metadata\Entity\SourceField;
use Gally\Metadata\Job\AbstractSourceFieldImport;
use Gally\Search\Entity\Facet\Configuration;
use Gally\Search\Repository\Facet\ConfigurationRepository;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductSourceFieldImport extends AbstractSourceFieldImport
{
    public const JOB_PROFILE = 'sourcefield_import';
    public const METADATA_ENTITY = 'product';

    protected ConfigurationRepository $facetConfigurationRepository;

    public const CSV_HEADERS = [
        ...parent::BASE_CSV_HEADERS,
        // Facet configuration
        'display_mode',
        'coverage_rate',
        'max_size',
        'sort_order',
        'position',
        'boolean_logic',
    ];

    public function __construct(
        protected JobManager $jobManager,
        protected EntityManagerFactory $entityManagerFactory,
        protected ValidatorInterface $validator,
        protected TranslatorInterface $translator,
        private int $batchSize = 100000,
    ) {
        parent::__construct($jobManager, $entityManagerFactory, $validator, $translator, $this->batchSize);
        $this->facetConfigurationRepository = $this->importEntityManager->getRepository(Configuration::class);
    }

    public function getLabel(): string
    {
        return $this->translator->trans('sourcefield.import.label', [], 'gally_sourcefield');
    }

    protected function initRepositories(): void
    {
        $this->facetConfigurationRepository = $this->importEntityManager->getRepository(Configuration::class);
    }

    protected function validateAdditionalFields(array $data, int $lineNumber): array
    {
        $errors = [];

        if (!empty($data['display_mode']) && !\in_array($data['display_mode'], Configuration::getAvailableDisplayModes(), true)) {
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

        return $errors;
    }

    protected function processAdditionalData(SourceField $sourceField, array $data): void
    {
        $facetConfiguration = $this->upsertFacetConfigurationFromData($sourceField, $data);

        if ($facetConfiguration) {
            $facetConfigurationViolations = $this->validator->validate($facetConfiguration);
            if (\count($facetConfigurationViolations) > 0) {
                $errors = [];
                foreach ($facetConfigurationViolations as $violation) {
                    $errors[] = $violation->getMessage();
                }
                throw new JobException($this->translator->trans('sourcefield.import.error.validation_failed', ['%errors%' => implode(', ', $errors)], 'gally_sourcefield'));
            }
        }
    }

    private function upsertFacetConfigurationFromData(SourceField $sourceField, array $data): ?Configuration
    {
        $facetConfig = $this->facetConfigurationRepository->findOneBySourceFieldAndDefaultCategory($sourceField);

        $tempConfig = $facetConfig ?? new Configuration($this->importEntityManager->getReference(SourceField::class, $sourceField->getId()), null);
        if (null === $facetConfig) {
            $tempConfig->initDefaultValue($tempConfig);
        }

        $displayMode = $data['display_mode'] === '' || $data['display_mode'] === $tempConfig->getDefaultDisplayMode() ? null : $data['display_mode'];
        $coverageRate = $data['coverage_rate'] === '' || (int) $data['coverage_rate'] === $tempConfig->getDefaultCoverageRate() ? null : ((int) $data['coverage_rate']);
        $maxSize = $data['max_size'] === '' || (int) $data['max_size'] === $tempConfig->getDefaultMaxSize() ? null : ((int) $data['max_size']);
        $sortOrder = $data['sort_order'] === '' || $data['sort_order'] === $tempConfig->getDefaultSortOrder() ? null : ($data['sort_order']);
        $position = $data['position'] === '' || (int) $data['position'] === $tempConfig->getDefaultPosition() ? null : (int) $data['position'];
        $booleanLogic = $data['boolean_logic'] === '' || strtoupper($data['boolean_logic']) === $tempConfig->getDefaultBooleanLogic() ? null : strtoupper($data['boolean_logic']);

        $allDefault = null === $displayMode
            && null === $coverageRate
            && null === $maxSize
            && null === $sortOrder
            && null === $position
            && null === $booleanLogic;

        // Skip creation if no config exists and all values are default/empty, or if not filterable.
        $skipReasons = [];
        if (null === $facetConfig && $allDefault) {
            $skipReasons[] = $this->translator->trans('sourcefield.import.skip_reason.all_default', [], 'gally_sourcefield');
        }
        if (!$sourceField->getIsFilterable()) {
            $skipReasons[] = $this->translator->trans('sourcefield.import.skip_reason.not_filterable', [], 'gally_sourcefield');
        }

        if (!empty($skipReasons)) {
            $this->logInfo(
                'sourcefield.import.skipping.default_facet_configuration',
                'gally_sourcefield',
                ['%code%' => $sourceField->getCode(), '%reason%' => implode(', ', $skipReasons)],
            );

            return null;
        }

        if (null === $facetConfig) {
            $facetConfig = $tempConfig;
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

        $facetConfig->setDisplayMode($displayMode);
        $facetConfig->setCoverageRate($coverageRate);
        $facetConfig->setMaxSize($maxSize);
        $facetConfig->setSortOrder($sortOrder);
        $facetConfig->setPosition($position);
        $facetConfig->setBooleanLogic($booleanLogic);

        return $facetConfig;
    }
}
