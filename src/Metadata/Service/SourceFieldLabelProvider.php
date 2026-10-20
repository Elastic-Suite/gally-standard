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
use Gally\Metadata\Repository\MetadataRepository;
use Gally\Metadata\Repository\SourceFieldRepository;

class SourceFieldLabelProvider
{
    /** @var array<string, array<string, string>> */
    protected array $localCache = [];

    public function __construct(
        private CacheManagerInterface $cacheManager,
        private MetadataRepository $metadataRepository,
        private SourceFieldRepository $sourceFieldRepository,
    ) {
    }

    /**
     * Resolves source field labels for given localized catalog and codes
     * Unknown codes are returned as-is, with first letter capitalized.
     *
     * @param string[] $codes
     *
     * @return array<int, array{code: string, label: string}>
     */
    public function getLabels(string $entityType, LocalizedCatalog $localizedCatalog, array $codes): array
    {
        $labelByCode = $this->getLabelMap($entityType, $localizedCatalog, $codes);

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
     * Return the code => label map of the requested source fields, for this localized catalog.
     *
     * @param string[] $codes
     *
     * @return array<string, string>
     */
    protected function getLabelMap(string $entityType, LocalizedCatalog $localizedCatalog, array $codes): array
    {
        $codes = array_values(array_unique($codes));
        sort($codes);
        $cacheKey = $entityType . '_' . $localizedCatalog->getId() . '_' . md5(json_encode($codes, \JSON_THROW_ON_ERROR));

        if (!isset($this->localCache[$cacheKey])) {
            $this->localCache[$cacheKey] = $this->cacheManager->get(
                'gally_source_field_labels_' . $cacheKey,
                function (&$tags, &$ttl) use ($entityType, $localizedCatalog, $codes): array {
                    // Kept: an unknown entity type must still be rejected with a clear message.
                    $metadata = $this->metadataRepository->findByEntity($entityType);

                    $rows = $this->sourceFieldRepository->getRawSourceFieldLabelsByCodes(
                        $metadata->getId(),
                        $codes,
                        $localizedCatalog->getId()
                    );

                    $labelByCode = [];
                    foreach ($rows as $row) {
                        $labelByCode[$row['code']] = $row['label'] ?? ucfirst($row['code']);
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
