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

namespace Gally\Index\Service;

use Gally\Index\Converter\SourceField\SourceFieldConverterInterface;
use Gally\Index\Entity\Index\Mapping;
use Gally\Index\Entity\Index\Mapping\FieldInterface;
use Gally\Metadata\Entity\Metadata;
use Gally\Metadata\Entity\SourceField;

class MetadataManager
{
    private array $cache = [];

    /**
     * @param SourceFieldConverterInterface[] $sourceFieldConverters Source field converters
     */
    public function __construct(
        private iterable $sourceFieldConverters = []
    ) {
        $sourceFieldConverters = ($sourceFieldConverters instanceof \Traversable) ? iterator_to_array($sourceFieldConverters) : $sourceFieldConverters;

        $this->sourceFieldConverters = $sourceFieldConverters;
    }

    /**
     * Create elasticsearch index mapping from metadata entity.
     */
    public function getMapping(Metadata $metadata): Mapping
    {
        if (!isset($this->cache[$metadata->getEntity()])) {
            $fields = [];

            // Dynamic fields
            foreach ($metadata->getSourceFields() as $sourceField) {
                $fields = $this->getFields($sourceField) + $fields;
            }

            $this->cache[$metadata->getEntity()] = new Mapping($fields);
        }

        return $this->cache[$metadata->getEntity()];
    }

    /**
     * @return FieldInterface[]
     */
    public function getFields(SourceField $sourceField): array
    {
        $fields = [];
        foreach ($this->sourceFieldConverters as $converter) {
            if ($converter->supports($sourceField)) {
                $fields = $converter->getFields($sourceField) + $fields;
            }
        }

        return $fields;
    }

    public function getMappingStatus(Metadata $metadata): Mapping\Status
    {
        foreach ($metadata->getSourceFields() as $sourceField) {
            if (!$sourceField->getType()) {
                return new Mapping\Status($metadata->getEntity(), Mapping\Status::Red);
            }
        }

        // @Todo Check mapping status in current index to check if it is the latest version.

        return new Mapping\Status($metadata->getEntity(), Mapping\Status::Green);
    }

    public function cleanLocalCache(): void
    {
        $this->cache = [];
    }
}
