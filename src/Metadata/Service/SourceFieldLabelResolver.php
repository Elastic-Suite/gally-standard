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
use Gally\Catalog\Entity\LocalizedCatalog;
use Gally\Metadata\Entity\SourceField;
use Gally\Metadata\Repository\MetadataRepository;
use Gally\Metadata\Repository\SourceFieldRepository;

/**
 * Resolves source field labels for a given localized catalog.
 *
 * An unknown code is answered with ucfirst($code), which is exactly what a real source field
 * without any label returns. The response therefore does not say which codes exist.
 *
 * Level 1 — local PHP array: avoids repeated Redis round-trips within the same request.
 * Level 2 — Redis (via CacheManager): persists the computed map across requests.
 *
 * The whole entity/catalog map is cached once and then filtered in memory. Caching per requested
 * code list would create one Redis entry per caller.
 */
class SourceFieldLabelResolver
{
    /** @var array<string, array<string, string>> */
    private array $localCache = [];

    public function __construct(
        private CacheManagerInterface $cacheManager,
        private MetadataRepository $metadataRepository,
        private SourceFieldRepository $sourceFieldRepository,
    ) {
    }

    /**
     * Return the label of each requested code, in the order given and without duplicates.
     *
     * @param string[] $codes
     *
     * @return array<int, array{code: string, label: string}>
     */
    public function getLabels(string $entityType, LocalizedCatalog $localizedCatalog, array $codes): array
    {
        $labelByCode = $this->getLabelMap($entityType, $localizedCatalog);

        $labels = [];
        foreach (array_unique($codes) as $code) {
            $labels[] = [
                'code' => $code,
                'label' => $labelByCode[$code] ?? ucfirst($code),
            ];
        }

        return $labels;
    }

    /**
     * Return the code => label map of every source field of the entity, for this localized catalog.
     *
     * @return array<string, string>
     */
    private function getLabelMap(string $entityType, LocalizedCatalog $localizedCatalog): array
    {
        $cacheKey = $entityType . '_' . $localizedCatalog->getId();

        if (!isset($this->localCache[$cacheKey])) {
            $this->localCache[$cacheKey] = $this->cacheManager->get(
                'gally_source_field_labels_' . $cacheKey,
                function (&$tags, &$ttl) use ($entityType, $localizedCatalog): array {
                    $metadata = $this->metadataRepository->findByEntity($entityType);
                    /** @var SourceField[] $sourceFields */
                    $sourceFields = $this->sourceFieldRepository->findBy(['metadata' => $metadata]);
                    if (empty($sourceFields)) {
                        return [];
                    }

                    $labels = $this->sourceFieldRepository->getLabelsBySourceFields($sourceFields, $localizedCatalog);

                    $labelByCode = [];
                    foreach ($sourceFields as $sourceField) {
                        $labelByCode[$sourceField->getCode()] = $labels[$sourceField->getId()]['label']
                            ?? ucfirst($sourceField->getCode());
                    }

                    return $labelByCode;
                },
                [
                    MetadataSourceFieldProviderCache::CACHE_TAG_SOURCE_FIELDS,
                    MetadataSourceFieldProviderCache::getEntityTag($entityType),
                ]
            );
        }

        return $this->localCache[$cacheKey];
    }
}
