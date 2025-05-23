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

namespace Gally\Stitching\Service;

use Gally\Metadata\Constant\SourceFieldAttributeMapping;
use Gally\Metadata\Repository\MetadataRepository;

class SerializerService
{
    private array $sourceFieldsStitchingCache = [];

    private const STITCHING_CONFIGURATION = 'stitching_config';

    public function __construct(
        private MetadataRepository $metadataRepository,
    ) {
    }

    /**
     * Transform the requested attributes structure as provided in the context into a flattened array
     * of potential attribute/source field codes.
     * This is required to handle individual nested fields, but it will produce false positives
     * for request attributes linked to source fields of complex type (types 'select, 'price', 'stock', etc.).
     *
     * @param array $contextAttributes Structured context attributes
     */
    public function getFlattenedContextAttributes(array $contextAttributes): array
    {
        $flattened = [];

        foreach ($contextAttributes as $attributeCode => $subStructure) {
            if (\is_array($subStructure)) {
                // Structured attribute: nested or complex source field.
                $complexAttributeCodes = array_map(
                    function ($subAttribute) use ($attributeCode) {
                        return \sprintf('%s.%s', $attributeCode, $subAttribute);
                    },
                    array_keys($subStructure)
                );
                $flattened = array_merge($flattened, $complexAttributeCodes);
            }
            $flattened[] = $attributeCode;
        }

        return $flattened;
    }

    /**
     * Retrieve the stitching configuration for hydrating source fields at the 'denormalize' stage,
     * either for all source fields of a given entity or for a given list of entity source fields
     * identified by a list of context attributes.
     *
     * @param string   $entityType        Entity type
     * @param string[] $contextAttributes Structured context attributes
     *
     * @return array An array whose keys are the source field codes and the values the stitching class
     *               to use when hydrating/de-normalizing those source fields.
     *               For nested source fields, the key is the field nested path and the value is an array
     *               with the same structure described above, for every source field sharing the same path.
     */
    public function getStitchingConfigFromContextAttributes(string $entityType, array $contextAttributes = []): array
    {
        $sourceFieldCodes = [];
        if (!empty($contextAttributes)) {
            $sourceFieldCodes = $this->getFlattenedContextAttributes($contextAttributes);
        }

        return $this->getStitchingConfigFromSourceFields($entityType, $sourceFieldCodes);
    }

    /**
     * Retrieve the stitching configuration for hydrating source fields at the 'denormalize' stage,
     * either for all source fields of a given entity or for a given list of entity source fields.
     *
     * @param string   $entityType       Entity type
     * @param string[] $sourceFieldCodes An array of source field codes
     *
     * @return array An array whose keys are the source field codes and the values the stitching class
     *               to use when hydrating/de-normalizing those source fields.
     *               For nested source fields, the key is the field nested path and the value is an array
     *               with the same structure described above, for every source field sharing the same path.
     */
    public function getStitchingConfigFromSourceFields(string $entityType, array $sourceFieldCodes = []): array
    {
        $cacheKey = $this->getCacheKey(self::STITCHING_CONFIGURATION, $entityType, $sourceFieldCodes);
        if (!isset($this->sourceFieldsStitchingCache[$cacheKey])) {
            $sourceFieldsTypes = [];

            if ($metadata = $this->metadataRepository->findByEntity($entityType)) {
                $sourceFields = !empty($sourceFieldCodes)
                    ? $metadata->getSourceFieldByCodes($sourceFieldCodes) // Loading only the provided list of source fields.
                    : $metadata->getSourceFields();

                foreach ($sourceFields as $sourceField) {
                    $sourceFieldCode = $sourceField->getCode();
                    if (!\in_array($sourceField->getType(), array_keys(SourceFieldAttributeMapping::TYPES), true)) {
                        continue;
                    }
                    if ($sourceField->isNested()) {
                        [$path, $field] = [$sourceField->getNestedPath(), $sourceField->getNestedCode()];
                        $sourceFieldsTypes[$path][$field] = SourceFieldAttributeMapping::TYPES[$sourceField->getType()];
                    } else {
                        $sourceFieldsTypes[$sourceFieldCode] = SourceFieldAttributeMapping::TYPES[$sourceField->getType()];
                    }
                }
            }

            $this->sourceFieldsStitchingCache[$cacheKey] = $sourceFieldsTypes;
        }

        return $this->sourceFieldsStitchingCache[$cacheKey];
    }

    private function getCacheKey(string $configType, string $entityType, array $sourceFieldCodes): string
    {
        return hash('sha256', implode('|', [$configType, $entityType, ...$sourceFieldCodes]));
    }
}
