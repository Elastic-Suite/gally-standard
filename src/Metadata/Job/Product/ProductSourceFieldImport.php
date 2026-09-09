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

namespace Gally\Metadata\Job\Product;

use Gally\Doctrine\Service\EntityManagerFactory;
use Gally\Job\Exception\JobException;
use Gally\Job\Service\JobManager;
use Gally\Metadata\Entity\Metadata;
use Gally\Metadata\Entity\SourceField;
use Gally\Metadata\Job\AbstractSourceFieldImport;
use Gally\Metadata\Validator\SourceFieldDataValidator;
use Gally\Search\Entity\Facet\Configuration;
use Gally\Search\Repository\Facet\ConfigurationRepository;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductSourceFieldImport extends AbstractSourceFieldImport
{
    public const JOB_PROFILE = 'source_field_import';
    public const METADATA_ENTITY = 'product';

    protected ConfigurationRepository $facetConfigurationRepository;

    /** Codes of source fields whose facet configuration was skipped, grouped by reason and logged once at the end of processing. */
    private array $facetConfigurationSkips = [];

    /** Source field used to validate facet values on their own, without the state of the real one. */
    private ?SourceField $validationSourceField = null;

    private const FACET_CONFIGURATION_CSV_FIELDS = [
        'display_mode',
        'coverage_rate',
        'max_size',
        'sort_order',
        'position',
        'boolean_logic',
    ];

    /**
     * Facet columns validated against the constraints of Configuration, in the order they appear in the CSV.
     *
     * Each entry is [entity property, cast to apply to the raw CSV value, translation key of the error message].
     * The rules themselves live in Search/Resources/config/validator/validation.yaml, not here.
     */
    private const FACET_VALIDATION_MAP = [
        'display_mode' => ['displayMode', null, 'invalid_display_mode'],
        'coverage_rate' => ['coverageRate', 'int', 'invalid_coverage_rate'],
        'max_size' => ['maxSize', 'int', 'invalid_max_size'],
        'sort_order' => ['sortOrder', null, 'invalid_sort_order'],
        'position' => ['position', 'int', 'invalid_position'],
        'boolean_logic' => ['booleanLogic', 'upper', 'invalid_boolean_logic'],
    ];

    public const CSV_HEADERS = [
        ...parent::BASE_CSV_HEADERS,
        ...self::FACET_CONFIGURATION_CSV_FIELDS,
    ];

    public function __construct(
        protected JobManager $jobManager,
        protected EntityManagerFactory $entityManagerFactory,
        protected ValidatorInterface $validator,
        protected TranslatorInterface $translator,
        protected SourceFieldDataValidator $sourceFieldDataValidator,
        private int $batchSize = 100000,
    ) {
        parent::__construct($jobManager, $entityManagerFactory, $validator, $translator, $sourceFieldDataValidator, $this->batchSize);
        $this->facetConfigurationRepository = $this->importEntityManager->getRepository(Configuration::class);
    }

    public function getLabel(): string
    {
        return $this->translator->trans('source_field.import.label', [], 'gally_source_field');
    }

    protected function initRepositories(): void
    {
        $this->facetConfigurationRepository = $this->importEntityManager->getRepository(Configuration::class);
    }

    protected function validateAdditionalFields(array $data, int $lineNumber): array
    {
        return $this->validateEntityFields(
            new Configuration($this->getFakeValidationSourceField(), null),
            self::FACET_VALIDATION_MAP,
            $data
        );
    }

    /**
     * Creates a filterable source field used only for validating the facet configuration
     */
    private function getFakeValidationSourceField(): SourceField
    {
        if (null === $this->validationSourceField) {
            $this->validationSourceField = (new SourceField())
                ->setCode('__facet_validation__')
                ->setMetadata((new Metadata())->setEntity(static::METADATA_ENTITY))
                ->setIsFilterable(true);
        }

        return $this->validationSourceField;
    }

    protected function getAdditionalSystemUpdatableCsvFields(): array
    {
        return self::FACET_CONFIGURATION_CSV_FIELDS;
    }

    protected function processAdditionalData(SourceField $sourceField, array $data): void
    {
        $this->upsertFacetConfigurationFromData($sourceField, $data);
    }

    /**
     * Create a new facet configuration if the imported config does not match the default.
     * If a configuration exists it updates it.
     * In both case this function ensure the resulting line contains only non-default values.
     *
     * @throws JobException
     *
     * @return null
     */
    private function upsertFacetConfigurationFromData(SourceField $sourceField, array $data)
    {
        $facetConfig = $this->facetConfigurationRepository->findOneBySourceFieldAndDefaultCategory($sourceField);

        $sourceFieldReference = $this->importEntityManager->getReference(SourceField::class, $sourceField->getId());
        $tempConfig = $facetConfig ?? new Configuration($sourceFieldReference, null);

        $defaults = new Configuration($sourceFieldReference, null);
        $defaults->initDefaultValue($defaults);

        $displayMode = $this->getValueIfNotNullOrNotDefault($data, 'display_mode', $defaults->getDefaultDisplayMode());
        $coverageRate = $this->getValueIfNotNullOrNotDefault($data, 'coverage_rate', $defaults->getDefaultCoverageRate(), intval(...));
        $maxSize = $this->getValueIfNotNullOrNotDefault($data, 'max_size', $defaults->getDefaultMaxSize(), intval(...));
        $sortOrder = $this->getValueIfNotNullOrNotDefault($data, 'sort_order', $defaults->getDefaultSortOrder());
        $position = $this->getValueIfNotNullOrNotDefault($data, 'position', $defaults->getDefaultPosition(), intval(...));
        $booleanLogic = $this->getValueIfNotNullOrNotDefault($data, 'boolean_logic', $defaults->getDefaultBooleanLogic(), strtoupper(...));

        $allDefault = null === $displayMode
            && null === $coverageRate
            && null === $maxSize
            && null === $sortOrder
            && null === $position
            && null === $booleanLogic;

        // Skip creation if no config exists and all values are default/empty, or if not filterable.
        $skipReasons = [];
        if (null === $facetConfig && $allDefault) {
            $skipReasons[] = $this->translator->trans('source_field.import.skip_reason.all_default', [], 'gally_source_field');
        }

        if (!$sourceField->getIsFilterable()) {
            $skipReasons[] = $this->translator->trans('source_field.import.skip_reason.not_filterable', [], 'gally_source_field');
        }

        if (!empty($skipReasons)) {
            $this->facetConfigurationSkips[implode(', ', $skipReasons)][] = $sourceField->getCode();

            return null;
        }

        if (null === $facetConfig) {
            $facetConfig = $tempConfig;
            $this->importEntityManager->persist($facetConfig);
            $this->logInfo(
                'source_field.import.creating.default_facet_configuration',
                'gally_source_field',
                ['%code%' => $sourceField->getCode()],
            );
        } else {
            $this->logInfo(
                'source_field.import.updating.default_facet_configuration',
                'gally_source_field',
                ['%code%' => $sourceField->getCode()],
            );
        }

        $facetConfig->setDisplayMode($displayMode);
        $facetConfig->setCoverageRate($coverageRate);
        $facetConfig->setMaxSize($maxSize);
        $facetConfig->setSortOrder($sortOrder);
        $facetConfig->setPosition($position);
        $facetConfig->setBooleanLogic($booleanLogic);

        $facetConfigurationViolations = $this->validator->validate($facetConfig);
        if (\count($facetConfigurationViolations) > 0) {
            $errors = [];
            foreach ($facetConfigurationViolations as $violation) {
                $errors[] = $violation->getMessage();
            }
            throw new JobException($this->translator->trans('source_field.import.error.validation_failed', ['%errors%' => implode(', ', $errors)], 'gally_source_field'));
        }
    }

    /**
     * Returns the imported value of a CSV column, or null when the column is missing, empty, or equals the resolved default.
     *
     * Storing null instead of the default value keeps the default applying.
     *
     * @template T of int|string
     *
     * @param T|null                     $default
     * @param (callable(string): T)|null $cast    applied to the raw CSV value before comparing it to the default
     *
     * @return T|null
     */
    private function getValueIfNotNullOrNotDefault(array $data, string $key, int|string|null $default, ?callable $cast = null): int|string|null
    {
        $rawValue = $data[$key] ?? null;
        if (null === $rawValue || '' === $rawValue) {
            return null;
        }

        $value = null !== $cast ? $cast((string) $rawValue) : (string) $rawValue;

        return $value === $default ? null : $value;
    }

    protected function afterProcessLines(): void
    {
        foreach ($this->facetConfigurationSkips as $reason => $codes) {
            $this->logInfo(
                'source_field.import.skipping.default_facet_configuration',
                'gally_source_field',
                ['%reason%' => $reason, '%codes%' => implode(', ', $codes)],
            );
        }
        $this->facetConfigurationSkips = [];
    }
}
