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
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductSourceFieldImport extends AbstractSourceFieldImport
{
    public const JOB_PROFILE = 'source_field_import';
    public const METADATA_ENTITY = 'product';

    protected ConfigurationRepository $facetConfigurationRepository;

    /** Codes of source fields whose facet configuration was skipped, grouped by reason and logged once at the end of processing. */
    private array $facetConfigurationSkips = [];

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
        // Create a temp filterable source field to avoid triggering the "is filterable" validation rule
        // Import can make a source field filterable and import facet config at the same time
        $fakeFilterableSourceField = new SourceField()
            ->setCode('__facet_validation__')
            ->setMetadata((new Metadata())->setEntity(static::METADATA_ENTITY))
            ->setIsFilterable(true);

        return $this->validateEntityFields(
            new Configuration($fakeFilterableSourceField, null),
            self::FACET_VALIDATION_MAP,
            $data
        );
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
     */
    private function upsertFacetConfigurationFromData(SourceField $sourceField, array $data): void
    {
        $facetConfig = $this->facetConfigurationRepository->findOneBySourceFieldAndDefaultCategory($sourceField);

        $sourceFieldReference = $this->importEntityManager->getReference(SourceField::class, $sourceField->getId());

        $defaults = new Configuration($sourceFieldReference, null);
        $defaults->initDefaultValue($defaults);

        $values = [
            'displayMode' => $this->getValueIfNotNullOrNotDefault($data, 'display_mode', $defaults->getDefaultDisplayMode()),
            'coverageRate' => $this->getValueIfNotNullOrNotDefault($data, 'coverage_rate', $defaults->getDefaultCoverageRate(), intval(...)),
            'maxSize' => $this->getValueIfNotNullOrNotDefault($data, 'max_size', $defaults->getDefaultMaxSize(), intval(...)),
            'sortOrder' => $this->getValueIfNotNullOrNotDefault($data, 'sort_order', $defaults->getDefaultSortOrder()),
            'position' => $this->getValueIfNotNullOrNotDefault($data, 'position', $defaults->getDefaultPosition(), intval(...)),
            'booleanLogic' => $this->getValueIfNotNullOrNotDefault($data, 'boolean_logic', $defaults->getDefaultBooleanLogic(), strtoupper(...)),
        ];

        $allDefault = !array_filter($values, static fn ($value) => null !== $value);

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

            return;
        }

        $isNewConfig = null === $facetConfig;
        if ($isNewConfig) {
            $facetConfig = new Configuration($sourceFieldReference, null);
        }
        $this->applyFacetValues($facetConfig, $values);
        // Sets the new source field values to ensure it is not validated against the current db source field values  
        $facetConfig->setSourceField($sourceField);

        $errors = [];
        foreach ($this->validator->validate($facetConfig) as $violation) {
            $errors[] = $violation->getMessage();
        }

        if ($errors) {
            throw new JobException($this->translator->trans('source_field.import.error.validation_failed', ['%errors%' => implode(', ', $errors)], 'gally_source_field'));
        }

        // Everything is valid and we can now safely persist the config if it's a new one
        if ($isNewConfig) {
            // Setting back to the source field reference to prevent entity manager to persist an entity it does not knows
            $facetConfig->setSourceField($sourceFieldReference);
            $this->importEntityManager->persist($facetConfig);
        }

        $this->logInfo(
            $isNewConfig
                ? 'source_field.import.creating.default_facet_configuration'
                : 'source_field.import.updating.default_facet_configuration',
            'gally_source_field',
            ['%code%' => $sourceField->getCode()],
        );
    }

    /**
     * @param array<string, int|string|null> $values keyed by entity property, as built in upsertFacetConfigurationFromData()
     */
    private function applyFacetValues(Configuration $facetConfig, array $values): Configuration
    {
        $facetConfig->setDisplayMode($values['displayMode']);
        $facetConfig->setCoverageRate($values['coverageRate']);
        $facetConfig->setMaxSize($values['maxSize']);
        $facetConfig->setSortOrder($values['sortOrder']);
        $facetConfig->setPosition($values['position']);
        $facetConfig->setBooleanLogic($values['booleanLogic']);

        return $facetConfig;
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
