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
use Gally\Metadata\Entity\SourceField;
use Gally\Metadata\Entity\SourceFieldOption;
use Gally\Metadata\Repository\SourceFieldOptionLabelRepository;
use Gally\Metadata\Repository\SourceFieldOptionRepository;
use Gally\Metadata\Repository\SourceFieldRepository;
use Gally\Metadata\Validator\SourceFieldOptionDataValidator;

class SourceFieldOptionProcessor implements ProcessorInterface
{
    private array $optionsData = [];
    private array $labelsData = [];
    private array $sourceFieldIds = [];
    private array $optionCodes = [];
    private array $errors = [];
    private string $routePrefix;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private SourceFieldRepository $sourceFieldRepository,
        private SourceFieldOptionRepository $sourceFieldOptionRepository,
        private SourceFieldOptionLabelRepository $sourceFieldOptionLabelRepository,
        private SourceFieldOptionDataValidator $validator,
        private ProcessorInterface $removeProcessor,
        string $routePrefix,
    ) {
        $this->routePrefix = $routePrefix ? '/' . $routePrefix : '';
    }

    /**
     * @param SourceFieldOption $data
     *
     * @throws Exception
     */
    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): ?SourceFieldOption
    {
        if ($operation instanceof DeleteOperationInterface) {
            return $this->removeProcessor->process($data, $operation, $uriVariables, $context);
        }

        return $this->persist($data);
    }

    /**
     * Persist source fields options.
     *
     * @param SourceFieldOption $data
     *
     * @throws Exception
     */
    public function persist($data): SourceFieldOption
    {
        $sourceFieldOption = $data;

        try {
            $this->entityManager->beginTransaction();
            $this->replaceLabels($sourceFieldOption);
            $this->entityManager->persist($sourceFieldOption);
            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            throw $e;
        }

        return $data;
    }

    /**
     * Persist multiple source field options with a minimum of database query.
     * For better performance, we have implemented this method voluntarily without using the ORM that was too slow for this purpose.
     */
    public function persistMultiple(array $options): array
    {
        $this->validateAndFormatData($options);

        $this->insertOptions();
        $this->insertOptionLabels();

        if (!empty($this->errors)) {
            throw new InvalidArgumentException(implode(' ', $this->errors));
        }

        return $this->sourceFieldOptionRepository->findBy(
            [
                'code' => $this->optionCodes,
                'sourceField' => $this->sourceFieldIds,
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
     * If we have these rows in source_field_option_label table: [id => 1, source_field__option_id => 1, localized_catalog_id => '1', 'label' => 'Name']
     * and we try to update them via a PUT endpoint by these data : [id => 1, source_field__option_id => 1, localized_catalog_id => '1', 'label' => 'Names']
     * during the save process API Platform will run an update query for the first row, but it will raise a 'Unique violation' error because there is already a label option row (id => 1) with the localized catalog 1 related to $sourceField.
     * To avoid this error we remove the labels related to $sourceFieldOption and we add them again if it's necessary.
     */
    protected function replaceLabels(SourceFieldOption $sourceFieldOption): void
    {
        // Retrieve original label from label repository in order to avoid entity manager cache issue.
        // And index them by localized catalog.
        $originalLabels = [];
        foreach ($this->sourceFieldOptionLabelRepository->findBy(['sourceFieldOption' => $sourceFieldOption]) as $originalLabel) {
            $originalLabels[$originalLabel->getLocalizedCatalog()->getId()] = $originalLabel;
        }

        $newLabels = [];
        foreach ($sourceFieldOption->getLabels() as $label) {
            $localizedCatalogId = $label->getLocalizedCatalog()->getId();
            if (\array_key_exists($localizedCatalogId, $originalLabels)) {
                $newLabel = $originalLabels[$localizedCatalogId];
                $newLabel->setLabel($label->getLabel());
                $newLabels[] = $newLabel;
                $sourceFieldOption->removeLabel($label);
                unset($originalLabels[$localizedCatalogId]);
            } else {
                $newLabels[] = $label;
            }
        }

        foreach ($originalLabels as $labelToRemove) {
            $this->entityManager->remove($labelToRemove);
        }

        $sourceFieldOption->setLabels(new ArrayCollection());
        foreach ($newLabels as $newLabel) {
            $sourceFieldOption->addLabel($newLabel);
        }
    }

    /**
     * Manually parse and validate data from the bulk request content.
     * This will fill the property $optionsData and $labelsData of this class with multi dimentional array.
     * The levels of these arrays are :
     *     - The source field id.
     *     - The source field option code.
     *   - The localized catalog id (only for $labelsData).
     *
     * The method perform the same validations as the default persist method.
     */
    private function validateAndFormatData(array $rawData)
    {
        $this->sourceFieldIds = array_unique(array_filter(
            array_map(
                fn ($item) => \array_key_exists('sourceField', $item)
                    ? (int) str_replace($this->routePrefix . '/source_fields/', '', $item['sourceField'])
                    : null,
                $rawData
            )
        ));
        $existingSourceFieldIds = empty($this->sourceFieldIds)
            ? []
            : $this->sourceFieldRepository->getRawSourceFieldTypeByIds($this->sourceFieldIds);

        // Parse data to sort option by sourceField and code
        foreach ($rawData as $index => $option) {
            try {
                $this->validator->validateRawData($option, $existingSourceFieldIds);
                $sourceFieldId = (int) str_replace($this->routePrefix . '/source_fields/', '', $option['sourceField']);

                // Manage labels data
                foreach ($option['labels'] ?? [] as $label) {
                    $localizedCatalogId = (int) str_replace($this->routePrefix . '/localized_catalogs/', '', $label['localizedCatalog']);
                    $this->labelsData[$sourceFieldId][$option['code']][$localizedCatalogId] = $label;
                }

                $this->optionsData[$sourceFieldId][$option['code']] = $option;
                $this->optionCodes[$option['code']] = $option['code'];
            } catch (\Exception $exception) {
                $this->errors[] = \sprintf('Option #%d: %s', $index, $exception->getMessage());
            }
        }
    }

    /**
     * Get existing source fields option from database without using the ORM
     * (in order to avoid running the unserialization/normalization process).
     *  This will return a multidimensional array with these levels
     *     - The source field id.
     *     - The source field option code.
     */
    private function getExistingOptions(): array
    {
        $existingOptions = [];
        $rawData = $this->sourceFieldOptionRepository->getRawOptionDataByOptionCodes($this->sourceFieldIds, $this->optionCodes);
        foreach ($rawData as $existing) {
            $existingOptions[$existing['source_field_id']][$existing['code']] = $existing;
        }

        return $existingOptions;
    }

    /**
     * Get existing source field option labels from database without using the ORM
     * (in order to avoid running the unserialization/normalization process).
     *   This will return a multidimensional array with these levels
     *    - The source field id.
     *    - The source field option code.
     *    - The localized catalog id.
     */
    private function getExistingLabels(): array
    {
        $existingLabels = [];
        $rawData = $this->sourceFieldOptionLabelRepository->getRawLabelDataByOptionCodes($this->sourceFieldIds, $this->optionCodes);
        foreach ($rawData as $existing) {
            $existingLabels[$existing['source_field_id']][$existing['code']][$existing['localized_catalog_id']] = $existing;
        }

        return $existingLabels;
    }

    /**
     * Insert the current sourceField option batch in the database in a single query.
     */
    private function insertOptions()
    {
        $expBuilder = $this->entityManager->getExpressionBuilder();

        // Get existing option for these sourceFields
        $existingOptions = $this->getExistingOptions();

        $optionsToUpdate = [];
        foreach ($this->optionsData as $sourceFieldId => $sourceFieldOptions) {
            foreach ($sourceFieldOptions as $code => $optionData) {
                if (\array_key_exists($sourceFieldId, $existingOptions)
                    && \array_key_exists($code, $existingOptions[$sourceFieldId])
                ) {
                    // Update existing
                    $optionData = array_merge($existingOptions[$sourceFieldId][$code], $optionData);
                    $optionsToUpdate[] = [
                        'id' => (int) $optionData['id'],
                        'source_field_id' => (int) $optionData['source_field_id'],
                        'code' => $expBuilder->literal($optionData['code']),
                        'default_label' => $expBuilder->literal($optionData['defaultLabel']),
                        'position' => $optionData['position'] ? (int) $optionData['position'] : 'NULL',
                    ];
                } else {
                    // Create new
                    $optionsToUpdate[] = [
                        'id' => "nextval('source_field_option_id_seq')",
                        'source_field_id' => $sourceFieldId,
                        'code' => $expBuilder->literal($optionData['code']),
                        'default_label' => $expBuilder->literal($optionData['defaultLabel']),
                        'position' => isset($optionData['position']) ? (int) $optionData['position'] : 'NULL',
                    ];
                }
            }
        }

        // Insert options in db
        if (!empty($optionsToUpdate)) {
            $this->sourceFieldOptionRepository->massInsertOrUpdate($optionsToUpdate);
        }
    }

    /**
     * Insert the current sourceField option label batch in the database in a single query.
     */
    private function insertOptionLabels()
    {
        $expBuilder = $this->entityManager->getExpressionBuilder();

        // Update existing options list and get existing labels
        $existingOptions = $this->getExistingOptions();
        $existingLabels = $this->getExistingLabels();

        $labelsToUpdate = [];
        foreach ($this->labelsData as $sourceFieldId => $sourceFieldLabels) {
            foreach ($sourceFieldLabels as $code => $optionLabels) {
                foreach ($optionLabels as $localizedCatalogId => $optionLabelsData) {
                    if (\array_key_exists($sourceFieldId, $existingLabels)
                        && \array_key_exists($code, $existingLabels[$sourceFieldId])
                        && \array_key_exists($localizedCatalogId, $existingLabels[$sourceFieldId][$code])
                    ) {
                        // Update existing
                        $optionLabelsData = array_merge($existingLabels[$sourceFieldId][$code][$localizedCatalogId], $optionLabelsData);
                        $labelsToUpdate[] = [
                            'id' => (int) $optionLabelsData['id'],
                            'localized_catalog_id' => (int) $optionLabelsData['localized_catalog_id'],
                            'source_field_option_id' => (int) $optionLabelsData['source_field_option_id'],
                            'label' => $expBuilder->literal($optionLabelsData['label']),
                        ];
                        unset($existingLabels[$sourceFieldId][$code][$localizedCatalogId]);
                    } else {
                        // Create new
                        $labelsToUpdate[] = [
                            'id' => 'nextval(\'source_field_option_label_id_seq\')',
                            'localized_catalog_id' => (int) $localizedCatalogId,
                            'source_field_option_id' => (int) $existingOptions[$sourceFieldId][$code]['id'],
                            'label' => $expBuilder->literal($optionLabelsData['label']),
                        ];
                    }
                }
            }
        }

        // Insert labels in db
        if (!empty($labelsToUpdate)) {
            $this->sourceFieldOptionLabelRepository->massInsertOrUpdate($labelsToUpdate);
        }

        $labelsToDelete = [];
        foreach ($existingLabels as $sourceFieldLabels) {
            foreach ($sourceFieldLabels as $optionLabels) {
                foreach ($optionLabels as $optionLabelsData) {
                    $labelsToDelete[] = $optionLabelsData['id'];
                }
            }
        }

        // Remove inexistant labels
        if (!empty($labelsToDelete)) {
            $this->sourceFieldOptionLabelRepository->massDelete($labelsToDelete);
        }
    }
}
