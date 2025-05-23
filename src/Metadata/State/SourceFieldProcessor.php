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

namespace Gally\Metadata\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Gally\Catalog\Service\DefaultCatalogProvider;
use Gally\Index\Entity\Index\Mapping\FieldInterface;
use Gally\Metadata\Entity\SourceField;
use Gally\Metadata\Repository\SourceFieldLabelRepository;
use Gally\Metadata\Repository\SourceFieldRepository;
use Gally\Metadata\Validator\SourceFieldDataValidator;

class SourceFieldProcessor implements ProcessorInterface
{
    private array $sourceFieldData = [];
    private array $labelsData = [];
    private array $metadataIds = [];
    private array $sourceFieldCodes = [];
    private array $errors = [];
    private string $routePrefix;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private DefaultCatalogProvider $defaultCatalogProvider,
        private SourceFieldRepository $sourceFieldRepository,
        private SourceFieldLabelRepository $sourceFieldLabelRepository,
        private SourceFieldDataValidator $validator,
        private ProcessorInterface $removeProcessor,
        string $routePrefix,
    ) {
        $this->routePrefix = $routePrefix ? '/' . $routePrefix : '';
    }

    /**
     * @param SourceField $data
     *
     * @throws Exception
     */
    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): ?SourceField
    {
        if ($operation instanceof DeleteOperationInterface) {
            if ($data->getIsSystem()) {
                throw new InvalidArgumentException('You can`t remove system source field');
            }

            return $this->removeProcessor->process($data, $operation, $uriVariables, $context);
        }

        return $this->persist($data);
    }

    /**
     * Persist source field.
     *
     * @param SourceField $data
     *
     * @throws Exception
     */
    public function persist($data): SourceField
    {
        $sourceField = $data;

        try {
            $this->entityManager->beginTransaction();
            $this->validator->validateObject($sourceField);
            $this->replaceLabels($sourceField);

            $this->entityManager->persist($sourceField);
            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            throw $e;
        }

        $this->entityManager->clear();

        return $data;
    }

    /**
     * Persist multiple source fields with a minimum of database query.
     * For better performance, we have implemented this method voluntarily without using the ORM that was too slow for this purpose.
     */
    public function persistMultiple(array $sourceFields): array
    {
        $this->validateAndFormatData($sourceFields);

        $this->insertSourceFields();
        $this->insertSourceFieldLabels();

        if (!empty($this->errors)) {
            throw new InvalidArgumentException(implode(' ', $this->errors));
        }

        return $this->sourceFieldRepository->findBy(
            [
                'code' => $this->sourceFieldCodes,
                'metadata' => $this->metadataIds,
            ]
        );
    }

    /**
     * Replace sourceField labels by the ones provided in the request.
     *
     * To avoid 'Unique violation' error  from database on labels,
     * we have to delete all the label rows related to $sourceField and add them again.
     *
     * For example:
     * If we have these rows in source_field_label table: [id => 1, source_field_id => 1, localized_catalog_id => '1', 'label' => 'Name']
     * and we try to update them via a PUT endpoint by these data : [id => 1, source_field_id => 1, localized_catalog_id => '1', 'label' => 'Names']
     * during the save process API Platform will run an update query for the first row, but it will raise a 'Unique violation' error because there is already a label row (id => 1) with the localized catalog 1 related to $sourceField.
     * To avoid this error we remove the labels related to $sourceField and we add them again if it's necessary.
     */
    protected function replaceLabels(SourceField $sourceField): void
    {
        // Retrieve original label from label repository in order to avoid entity manager cache issue.
        // And index them by localized catalog.
        $originalLabels = [];
        foreach ($this->sourceFieldLabelRepository->findBy(['sourceField' => $sourceField]) as $originalLabel) {
            $originalLabels[$originalLabel->getLocalizedCatalog()->getId()] = $originalLabel;
        }

        $newLabels = [];
        foreach ($sourceField->getLabels() as $label) {
            $localizedCatalogId = $label->getLocalizedCatalog()->getId();
            if (\array_key_exists($localizedCatalogId, $originalLabels)) {
                $newLabel = $originalLabels[$localizedCatalogId];
                $newLabel->setLabel($label->getLabel());
                $newLabels[] = $newLabel;
                $sourceField->removeLabel($label);
                unset($originalLabels[$localizedCatalogId]);
            } else {
                $newLabels[] = $label;
            }
        }

        foreach ($originalLabels as $labelToRemove) {
            $this->entityManager->remove($labelToRemove);
        }

        // Force remove old labels before persist new ones.
        $this->entityManager->flush();

        $sourceField->setLabels(new ArrayCollection());
        foreach ($newLabels as $newLabel) {
            $sourceField->addLabel($newLabel);
        }
    }

    /**
     * Manually parse and validate data from the bulk request content.
     * This will fill the property $sourceFieldData and $labelsData of this class with multi dimentional array.
     * The levels of these arrays are :
     *   - The metadata id.
     *   - The source field code.
     *   - The localized catalog id (only for $labelsData).
     *
     * The method perform the same validations as the default persist method.
     */
    private function validateAndFormatData(array $rawData): void
    {
        $this->metadataIds = array_unique(array_filter(
            array_map(
                fn ($item) => \array_key_exists('metadata', $item)
                    ? (int) str_replace($this->routePrefix . '/metadata/', '', $item['metadata'])
                    : null,
                $rawData
            )
        ));

        $this->sourceFieldCodes = array_unique(array_filter(
            array_map(
                fn ($item) => $item['code'] ?? null,
                $rawData
            )
        ));

        $defaultLocalizedCatalog = $this->defaultCatalogProvider->getDefaultLocalizedCatalog();
        $existingSourceFields = $this->getExistingSourceFields();

        // Parse data to sort option by sourceField and code
        foreach ($rawData as $index => $sourceField) {
            try {
                $this->validator->validateRawData($sourceField, $existingSourceFields);
                $metadataId = (int) str_replace($this->routePrefix . '/metadata/', '', $sourceField['metadata']);
                $defaultLabel = $sourceField['defaultLabel'] ?? ucfirst($sourceField['code']);

                // Manage labels data
                foreach ($sourceField['labels'] ?? [] as $label) {
                    $localizedCatalogId = (int) str_replace($this->routePrefix . '/localized_catalogs/', '', $label['localizedCatalog']);
                    $this->labelsData[$metadataId][$sourceField['code']][$localizedCatalogId] = $label;

                    if ($localizedCatalogId === $defaultLocalizedCatalog->getId()) {
                        $defaultLabel = $label['label'];
                    }
                }

                $sourceField['search'] = "{$sourceField['code']} $defaultLabel";
                $this->sourceFieldData[$metadataId][$sourceField['code']] = $sourceField;
            } catch (\Exception $exception) {
                $this->errors[] = \sprintf('Option #%d: %s', $index, $exception->getMessage());
            }
        }
    }

    /**
     * Get existing source fields from database without using the ORM
     * (in order to avoid running the unserialization/normalization process).
     *  This will return a multidimensional array with these levels
     *     - The metadata id.
     *     - The source field code.
     */
    private function getExistingSourceFields(): array
    {
        $existingSourceFields = [];
        $rawSourceFieldData = $this->sourceFieldRepository->getRawSourceFieldDataByCodes(
            $this->metadataIds,
            $this->sourceFieldCodes
        );
        foreach ($rawSourceFieldData as $existing) {
            $existingSourceFields[$existing['metadata']][$existing['code']] = $existing;
        }

        return $existingSourceFields;
    }

    /**
     * Get existing source field labels from database without using the ORM
     * (in order to avoid running the unserialization/normalization process).
     *   This will return a multidimensional array with these levels
     *    - The metadata id.
     *    - The source field code.
     *    - The localized catalog id.
     */
    private function getExistingLabels(): array
    {
        $existingLabels = [];
        $rawData = $this->sourceFieldLabelRepository->getRawLabelDataBySourceFieldCodes($this->metadataIds, $this->sourceFieldCodes);
        foreach ($rawData as $existing) {
            $existingLabels[$existing['metadata_id']][$existing['code']][$existing['localized_catalog_id']] = $existing;
        }

        return $existingLabels;
    }

    /**
     * Insert the current sourceField batch in the database in a single query.
     */
    private function insertSourceFields()
    {
        $expBuilder = $this->entityManager->getExpressionBuilder();

        // Get existing sourceFields
        $existingSourceFields = $this->getExistingSourceFields();

        $sourceFieldsToUpdate = [];
        foreach ($this->sourceFieldData as $metadataId => $sourceFields) {
            foreach ($sourceFields as $code => $sourceFieldData) {
                if (\array_key_exists($metadataId, $existingSourceFields)
                    && \array_key_exists($code, $existingSourceFields[$metadataId])
                ) {
                    // Update existing
                    $sourceFieldData = array_merge($existingSourceFields[$metadataId][$code], $sourceFieldData);
                    $sourceFieldToUpdate = [
                        'id' => (int) $sourceFieldData['id'],
                        'metadata_id' => $expBuilder->literal($metadataId),
                        'code' => $expBuilder->literal($sourceFieldData['code']),
                        'default_label' => $sourceFieldData['defaultLabel'] ? $expBuilder->literal($sourceFieldData['defaultLabel']) : 'NULL',
                        'type' => $sourceFieldData['type'] ? $expBuilder->literal($sourceFieldData['type']) : 'NULL',
                        'weight' => $expBuilder->literal($sourceFieldData['weight']),
                        'is_searchable' => null === $sourceFieldData['isSearchable'] ? 'NULL' : ($sourceFieldData['isSearchable'] ? 'True' : 'False'),
                        'is_filterable' => null === $sourceFieldData['isFilterable'] ? 'NULL' : ($sourceFieldData['isFilterable'] ? 'True' : 'False'),
                        'is_sortable' => null === $sourceFieldData['isSortable'] ? 'NULL' : ($sourceFieldData['isSortable'] ? 'True' : 'False'),
                        'is_spellchecked' => null === $sourceFieldData['isSpellchecked'] ? 'NULL' : ($sourceFieldData['isSpellchecked'] ? 'True' : 'False'),
                        'is_used_for_rules' => null === $sourceFieldData['isUsedForRules'] ? 'NULL' : ($sourceFieldData['isUsedForRules'] ? 'True' : 'False'),
                        'is_used_in_autocomplete' => null === $sourceFieldData['isUsedInAutocomplete'] ? 'NULL' : ($sourceFieldData['isUsedInAutocomplete'] ? 'True' : 'False'),
                        'is_spannable' => null === $sourceFieldData['isSpannable'] ? 'NULL' : ($sourceFieldData['isSpannable'] ? 'True' : 'False'),
                        'default_search_analyzer' => $expBuilder->literal($sourceFieldData['defaultSearchAnalyzer']),
                        'is_system' => ($sourceFieldData['isSystem'] ?? false) ? 'True' : 'False',
                        'search' => $expBuilder->literal($sourceFieldData['search']),
                    ];
                } else {
                    // Create new
                    $sourceFieldToUpdate = [
                        'id' => "nextval('source_field_id_seq')",
                        'metadata_id' => $expBuilder->literal($metadataId),
                        'code' => $expBuilder->literal($sourceFieldData['code']),
                        'default_label' => isset($sourceFieldData['defaultLabel']) ? $expBuilder->literal($sourceFieldData['defaultLabel']) : 'NULL',
                        'type' => isset($sourceFieldData['type']) ? $expBuilder->literal($sourceFieldData['type']) : 'NULL',
                        'weight' => $expBuilder->literal(isset($sourceFieldData['weight']) ? (int) $sourceFieldData['weight'] : 1),
                        'is_searchable' => isset($sourceFieldData['isSearchable']) ? ($sourceFieldData['isSearchable'] ? 'True' : 'False') : 'NULL',
                        'is_filterable' => isset($sourceFieldData['isFilterable']) ? ($sourceFieldData['isFilterable'] ? 'True' : 'False') : 'NULL',
                        'is_sortable' => isset($sourceFieldData['isSortable']) ? ($sourceFieldData['isSortable'] ? 'True' : 'False') : 'NULL',
                        'is_spellchecked' => isset($sourceFieldData['isSpellchecked']) ? ($sourceFieldData['isSpellchecked'] ? 'True' : 'False') : 'NULL',
                        'is_used_for_rules' => isset($sourceFieldData['isUsedForRules']) ? ($sourceFieldData['isUsedForRules'] ? 'True' : 'False') : 'NULL',
                        'is_used_in_autocomplete' => isset($sourceFieldData['isUsedInAutocomplete']) ? ($sourceFieldData['isUsedInAutocomplete'] ? 'True' : 'False') : 'NULL',
                        'is_spannable' => isset($sourceFieldData['isSpannable']) ? ($sourceFieldData['isSpannable'] ? 'True' : 'False') : 'NULL',
                        'default_search_analyzer' => $expBuilder->literal(isset($sourceFieldData['defaultSearchAnalyzer']) ? (string) $sourceFieldData['defaultSearchAnalyzer'] : FieldInterface::ANALYZER_STANDARD),
                        'is_system' => ($sourceFieldData['isSystem'] ?? false) ? 'True' : 'False',
                        'search' => $expBuilder->literal($sourceFieldData['search']),
                    ];
                }

                // Validate if provided data for sourceField match the list of managed source field listed in the repository.
                if (
                    !empty(
                        array_diff_key(
                            $this->sourceFieldRepository->getManagedSourceFieldProperty(),
                            array_flip(array_keys($sourceFieldToUpdate))
                        )
                    )
                ) {
                    throw new InvalidArgumentException('Some sourceField properties are not managed in bulk data');
                }
                $sourceFieldsToUpdate[] = $sourceFieldToUpdate;
            }
        }

        // Insert options in db
        if (!empty($sourceFieldsToUpdate)) {
            $this->sourceFieldRepository->massInsertOrUpdate($sourceFieldsToUpdate);
        }
    }

    /**
     * Insert the current sourceField label batch in the database in a single query.
     */
    private function insertSourceFieldLabels()
    {
        $expBuilder = $this->entityManager->getExpressionBuilder();

        // Update existing options list and get existing labels
        $existingSourceFields = $this->getExistingSourceFields();
        $existingLabels = $this->getExistingLabels();

        $labelsToUpdate = [];
        foreach ($this->labelsData as $metadataId => $metadataLabels) {
            foreach ($metadataLabels as $code => $sourceFieldLabels) {
                foreach ($sourceFieldLabels as $localizedCatalogId => $sourceFieldLabelsData) {
                    if (\array_key_exists($metadataId, $existingLabels)
                        && \array_key_exists($code, $existingLabels[$metadataId])
                        && \array_key_exists($localizedCatalogId, $existingLabels[$metadataId][$code])
                    ) {
                        // Update existing
                        $sourceFieldLabelsData = array_merge($existingLabels[$metadataId][$code][$localizedCatalogId], $sourceFieldLabelsData);
                        $labelsToUpdate[] = [
                            'id' => (int) $sourceFieldLabelsData['id'],
                            'localized_catalog_id' => (int) $localizedCatalogId,
                            'source_field_id' => (int) $existingSourceFields[$metadataId][$code]['id'],
                            'label' => $expBuilder->literal($sourceFieldLabelsData['label']),
                        ];
                        unset($existingLabels[$metadataId][$code][$localizedCatalogId]);
                    } else {
                        // Create new
                        $labelsToUpdate[] = [
                            'id' => 'nextval(\'source_field_label_id_seq\')',
                            'localized_catalog_id' => (int) $localizedCatalogId,
                            'source_field_id' => (int) $existingSourceFields[$metadataId][$code]['id'],
                            'label' => $expBuilder->literal($sourceFieldLabelsData['label']),
                        ];
                    }
                }
            }
        }

        // Insert labels in db
        if (!empty($labelsToUpdate)) {
            $this->sourceFieldLabelRepository->massInsertOrUpdate($labelsToUpdate);
        }

        $labelsToDelete = [];
        foreach ($existingLabels as $metadataLabels) {
            foreach ($metadataLabels as $sourceFieldLabels) {
                foreach ($sourceFieldLabels as $sourceFieldLabelsData) {
                    $labelsToDelete[] = $sourceFieldLabelsData['id'];
                }
            }
        }

        // Remove inexistant labels
        if (!empty($labelsToDelete)) {
            $this->sourceFieldLabelRepository->massDelete($labelsToDelete);
        }
    }
}
