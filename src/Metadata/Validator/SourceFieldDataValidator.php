<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @package   Gally
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Gally\Metadata\Validator;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use Doctrine\ORM\EntityManagerInterface;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Metadata\Model\SourceField;
use Gally\Metadata\Model\SourceFieldLabel;
use Gally\Metadata\Repository\MetadataRepository;

class SourceFieldDataValidator
{
    private array $requiredFields = ['code', 'metadata'];
    private array $existingMetadataIds;
    private array $existingLocalizedCatalogIds;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private MetadataRepository $metadataRepository,
        private LocalizedCatalogRepository $localizedCatalogRepository,
    ) {
    }

    /**
     * Validate if the given object is a valid sourceField that can be persisted.
     *
     * @return void
     */
    public function validateObject(SourceField $sourceField)
    {
        // Is it an update ?
        if ($this->entityManager->getUnitOfWork()->isInIdentityMap($sourceField)) {
            // Call function computeChangeSets to get the entity changes from the function getEntityChangeSet.
            $this->entityManager->getUnitOfWork()->computeChangeSets();
            $changeSet = $this->entityManager->getUnitOfWork()->getEntityChangeSet($sourceField);
            // Prevent computed change set to take labels in account.
            $this->entityManager->clear(SourceFieldLabel::class);

            unset($changeSet['isSpellchecked']);
            unset($changeSet['weight']);

            // Prevent user to update a system source field, only the value of 'weight' and 'isSpellchecked' can be changed.
            if (\count($changeSet) > 0 && ($sourceField->getIsSystem() || ($changeSet['isSystem'][0] ?? false) === true)) {
                throw new InvalidArgumentException(sprintf("The source field '%s' cannot be updated because it is a system source field, only the value of 'weight' and 'isSpellchecked' can be changed.", $sourceField->getCode()));
            }
        }
    }

    /**
     * Validate if the given data are valid to be insert in db as a source field.
     *
     * @param array $rawData              source field data
     * @param array $existingSourceFields A multidimensional array with:
     *                                    - metadata id as first level keys
     *                                    - source field code as second level keys
     *                                    - data currently in the data as values
     */
    public function validateRawData(array $rawData, array $existingSourceFields): void
    {
        foreach ($this->requiredFields as $requiredField) {
            if (!\array_key_exists($requiredField, $rawData)) {
                throw new InvalidArgumentException("A $requiredField value is required for source field.");
            }
        }

        $metadataId = (int) str_replace('/metadata/', '', $rawData['metadata']);

        if (!\array_key_exists($metadataId, $this->getExistingMetadataIds())) {
            throw new InvalidArgumentException("Item not found for \"${$rawData['metadata']}\".");
        }

        // Prevent user to update a system source field, only the value of 'weight' and 'isSpellchecked' can be changed.
        if (isset($existingSourceFields[$metadataId][$rawData['code']])) {
            $existing = $existingSourceFields[$metadataId][$rawData['code']];
            if ($existing['isSystem']) {
                foreach ($rawData as $field => $value) {
                    if (
                        !\in_array($field, ['code', 'metadata', 'weight', 'isSpellchecked', 'labels'], true)
                        && $value !== $existingSourceFields[$metadataId][$rawData['code']][$field]
                    ) {
                        throw new InvalidArgumentException(sprintf("The source field '%s' cannot be updated because it is a system source field, only the value of 'weight' and 'isSpellchecked' can be changed.", $rawData['code']));
                    }
                }
            }
        }

        // validate labels data
        foreach ($rawData['labels'] ?? [] as $label) {
            $localizedCatalogId = (int) str_replace('/localized_catalogs/', '', $label['localizedCatalog']);

            if (!\array_key_exists($localizedCatalogId, $this->getExistingLocalizedCatalog())) {
                throw new InvalidArgumentException("Item not found for \"{$label['localizedCatalog']}\".");
            }
        }
    }

    private function getExistingMetadataIds(): array
    {
        if (!isset($this->existingMetadataIds)) {
            $this->existingMetadataIds = $this->metadataRepository->getAllIds();
        }

        return $this->existingMetadataIds;
    }

    private function getExistingLocalizedCatalog(): array
    {
        if (!isset($this->existingLocalizedCatalogIds)) {
            $this->existingLocalizedCatalogIds = $this->localizedCatalogRepository->getAllIds();
        }

        return $this->existingLocalizedCatalogIds;
    }
}
