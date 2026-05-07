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
use Gally\Metadata\Repository\MetadataRepository;
use Gally\Metadata\Repository\SourceFieldRepository;

/**
 * Two-level cache decorator for source field retrieval on Metadata entities.
 *
 * Level 1 — local PHP array: avoids repeated Redis round-trips within the same request.
 * Level 2 — Redis (via CacheManager): persists computed source field lists across requests.
 *
 * The Metadata entity methods remain fully usable directly when no cache is needed.
 */
class MetadataSourceFieldProviderCache
{
    public const CACHE_TAG_SOURCE_FIELDS = 'gally_metadata_source_fields';

    /** @var array<string, SourceField[]> */
    private array $localCache = [];

    public function __construct(
        private CacheManagerInterface $cacheManager,
        private MetadataRepository $metadataRepository,
        private SourceFieldRepository $sourceFieldRepository,
    ) {
    }

    /**
     * Returns filterable or used-for-rules source fields for the given metadata.
     *
     * @return SourceField[]
     */
    public function getFilterableSourceFields(Metadata $metadata): array
    {
        return $this->getFromLocalCache(
            'filterable_' . $metadata->getEntity(),
            fn () => $this->cacheManager->get(
                'gally_sf_filterable_' . $metadata->getEntity(),
                fn (&$tags, &$ttl) => $metadata->getFilterableSourceFields(),
                [self::CACHE_TAG_SOURCE_FIELDS, $this->getEntityTag($metadata)],
            )
        );
    }

    /**
     * Returns filterable-in-aggregation source fields for the given metadata.
     *
     * @return SourceField[]
     */
    public function getFilterableInAggregationSourceFields(Metadata $metadata): array
    {
        return $this->getFromLocalCache(
            'filterable_aggregation_' . $metadata->getEntity(),
            fn () => $this->cacheManager->get(
                'gally_sf_filterable_aggregation_' . $metadata->getEntity(),
                fn (&$tags, &$ttl) => $metadata->getFilterableInAggregationSourceFields(),
                [self::CACHE_TAG_SOURCE_FIELDS, $this->getEntityTag($metadata)],
            )
        );
    }

    /**
     * Returns sortable source fields for the given metadata.
     *
     * @return SourceField[]
     */
    public function getSortableSourceFields(Metadata $metadata): array
    {
        return $this->getFromLocalCache(
            'sortable_' . $metadata->getEntity(),
            fn () => $this->cacheManager->get(
                'gally_sf_sortable_' . $metadata->getEntity(),
                fn (&$tags, &$ttl) => $metadata->getSortableSourceFields(),
                [self::CACHE_TAG_SOURCE_FIELDS, $this->getEntityTag($metadata)],
            )
        );
    }

    /**
     * Returns all source fields for the given metadata, with local + Redis cache.
     * Accepts either a Metadata object or a string entity name.
     * Passing a string avoids a database round-trip when the result is already in local cache.
     *
     * @return SourceField[]
     */
    public function getSourceFields(Metadata|string $metadata): array
    {
        $entityName = $metadata instanceof Metadata ? $metadata->getEntity() : $metadata;

        if (null === $entityName) {
            return [];
        }

        return $this->getFromLocalCache(
            'all_' . $entityName,
            fn () => $this->cacheManager->get(
                'gally_sf_all_' . $entityName,
                function (&$tags, &$ttl) use ($metadata, $entityName): array {
                    if (!$metadata instanceof Metadata) {
                        $metadata = $this->metadataRepository->findByEntity($entityName);
                    }

                    return $this->sourceFieldRepository->findBy(['metadata' => $metadata]);
                },
                [self::CACHE_TAG_SOURCE_FIELDS, $entityName],
            )
        );
    }

    /**
     * Returns source fields matching the given codes for the given metadata.
     *
     * All source fields are cached once per metadata entity, then filtered in memory,
     * avoiding one Redis entry per code combination.
     *
     * @param string[] $codes
     *
     * @return SourceField[]
     */
    public function getSourceFieldByCodes(Metadata $metadata, array $codes): array
    {
        $allSourceFields = $this->getSourceFields($metadata);

        return array_values(
            array_filter(
                $allSourceFields,
                fn (SourceField $field) => \in_array($field->getCode(), $codes, true)
            )
        );
    }

    /**
     * Invalidates all cached source fields for a specific metadata entity (local + Redis).
     */
    public function invalidate(Metadata $metadata): void
    {
        $entity = $metadata->getEntity();
        foreach (array_keys($this->localCache) as $key) {
            if (str_ends_with($key, '_' . $entity)) {
                unset($this->localCache[$key]);
            }
        }
        $this->cacheManager->clearTags([$this->getEntityTag($metadata)]);
    }

    /**
     * Invalidates all cached source fields across all metadata entities (local + Redis).
     */
    public function invalidateAll(): void
    {
        $this->localCache = [];
        $this->cacheManager->clearTags([self::CACHE_TAG_SOURCE_FIELDS]);
    }

    public static function getEntityTag(Metadata|string $metadata): string
    {
        $entityName = $metadata instanceof Metadata ? $metadata->getEntity() : $metadata;

        return self::CACHE_TAG_SOURCE_FIELDS . '_' . $entityName;
    }

    /**
     * Returns the value from the local cache if present, otherwise calls $loader, stores and returns the result.
     *
     * @param callable(): SourceField[] $loader
     *
     * @return SourceField[]
     */
    private function getFromLocalCache(string $key, callable $loader): array
    {
        if (!\array_key_exists($key, $this->localCache)) {
            $this->localCache[$key] = $loader();
        }

        return $this->localCache[$key];
    }
}
