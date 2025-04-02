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

namespace Gally\Metadata\Service;

use Gally\Cache\Service\CacheManagerInterface;
use Gally\Metadata\Entity\Metadata;
use Gally\Metadata\Entity\SourceField;

class MetadataManager
{
    private const CACHE_TAG = 'gally_source_fields';
    private array $sourceFields = [];
    private array $filterableSourceFields = [];
    private array $filterableInAggregationSourceFields = [];
    private array $sortableSourceFields = [];

    /**
     * @param CacheManagerInterface $cacheManager Cache manager
     */
    public function __construct(
        private CacheManagerInterface $cacheManager
    ) {
    }

    public function getSourceFields(Metadata $metadata): array
    {
        if (!isset($this->sourceFieldCache[$metadata->getEntity()])) {
            $this->sourceFields[$metadata->getEntity()] = $this->cacheManager->get(
                self::CACHE_TAG . '_' . $metadata->getEntity(),
                function (&$tags, &$ttl) use ($metadata) {
                    $sourceFields = [];
                    foreach ($metadata->getSourceFields() as $sourceField) {
                        $sourceFields[$sourceField->getCode()] = $sourceField;
                    }

                    return $sourceFields;
                },
                [self::CACHE_TAG]
            );
        }

        return $this->sourceFields[$metadata->getEntity()];
    }

    public function getSourceFieldByCodes(Metadata $metadata, array $codes): array
    {
        $sourceFields = $this->getSourceFields($metadata);

        return array_values(array_intersect_key($sourceFields, array_flip($codes)));
    }

    /**
     * @return SourceField[]
     */
    public function getFilterableSourceFields(Metadata $metadata): array
    {
        if (!isset($this->filterableSourceFields)
            || !\array_key_exists($metadata->getEntity(), $this->filterableSourceFields)
        ) {
            $filterableSourceFields = [];
            foreach ($this->getSourceFields($metadata) as $field) {
                if ($field->getIsFilterable() || $field->getIsUsedForRules()) {
                    $filterableSourceFields[] = $field;
                }
            }
            $this->filterableSourceFields[$metadata->getEntity()] = $filterableSourceFields;
        }

        return $this->filterableSourceFields[$metadata->getEntity()];
    }

    /**
     * @return SourceField[]
     */
    public function getFilterableInAggregationSourceFields(Metadata $metadata): array
    {
        if (!isset($this->filterableInAggregationSourceFields)
            || !\array_key_exists($metadata->getEntity(), $this->filterableInAggregationSourceFields)
        ) {
            $filterableInAggregationSourceFields = [];
            foreach ($this->getFilterableSourceFields($metadata) as $field) {
                if ($field->getIsFilterable()) {
                    $filterableInAggregationSourceFields[] = $field;
                }
            }
            $this->filterableInAggregationSourceFields[$metadata->getEntity()] = $filterableInAggregationSourceFields;
        }

        return $this->filterableInAggregationSourceFields[$metadata->getEntity()];
    }

    /**
     * @return SourceField[]
     */
    public function getSortableSourceFields(Metadata $metadata): array
    {
        if (!isset($this->sortableSourceFields)
            || !\array_key_exists($metadata->getEntity(), $this->sortableSourceFields)
        ) {
            $sortableSourceFields = [];
            foreach ($this->getSourceFields($metadata) as $field) {
                if ($field->getIsSortable()) {
                    $sortableSourceFields[] = $field;
                }
            }
            $this->sortableSourceFields[$metadata->getEntity()] = $sortableSourceFields;
        }

        return $this->sortableSourceFields[$metadata->getEntity()];
    }

    public function cleanCache(): void
    {
        $this->sourceFields = [];
        $this->filterableSourceFields = [];
        $this->filterableInAggregationSourceFields = [];
        $this->sortableSourceFields = [];
        $this->cacheManager->clearTags([self::CACHE_TAG]);
    }
}
