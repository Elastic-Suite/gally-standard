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

namespace Gally\Metadata\Validator;

use ApiPlatform\Exception\InvalidArgumentException;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Metadata\Entity\SourceField;

class SourceFieldOptionDataValidator
{
    private array $requiredFields = ['sourceField', 'code', 'defaultLabel'];
    private array $existingLocalizedCatalogIds;

    public function __construct(
        private LocalizedCatalogRepository $localizedCatalogRepository,
    ) {
    }

    /**
     * Validate if the given data are valid to be insert in db as a source field option.
     *
     * @param array $rawData                     source field option data
     * @param array $existingSourceFieldTypeById An associative array containing the existing sourceField id as key
     *                                           and the type of the sourceField as value
     *
     * @return void
     */
    public function validateRawData(array $rawData, array $existingSourceFieldTypeById)
    {
        foreach ($this->requiredFields as $requiredField) {
            if (!\array_key_exists($requiredField, $rawData)) {
                throw new InvalidArgumentException("A $requiredField value is required for source field option.");
            }
        }
        $sourceFieldId = (int) str_replace('/source_fields/', '', $rawData['sourceField']);

        if (!\array_key_exists($sourceFieldId, $existingSourceFieldTypeById)) {
            throw new InvalidArgumentException("Item not found for \"{$rawData['sourceField']}\".");
        }
        if (SourceField\Type::TYPE_SELECT != $existingSourceFieldTypeById[$sourceFieldId]['type']) {
            throw new InvalidArgumentException('You can only add options to a source field of type "select".');
        }

        // validate labels data
        foreach ($rawData['labels'] ?? [] as $label) {
            $localizedCatalogId = (int) str_replace('/localized_catalogs/', '', $label['localizedCatalog']);

            if (!\array_key_exists($localizedCatalogId, $this->getExistingLocalizedCatalog())) {
                throw new InvalidArgumentException("Item not found for \"{$label['localizedCatalog']}\".");
            }
        }
    }

    private function getExistingLocalizedCatalog(): array
    {
        if (!isset($this->existingLocalizedCatalogIds)) {
            $this->existingLocalizedCatalogIds = $this->localizedCatalogRepository->getAllIds();
        }

        return $this->existingLocalizedCatalogIds;
    }
}
