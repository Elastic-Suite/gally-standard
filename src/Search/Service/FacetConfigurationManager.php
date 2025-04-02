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

namespace Gally\Search\Service;

use Gally\Cache\Service\CacheManagerInterface;
use Gally\Metadata\Entity\Metadata;
use Gally\Metadata\Entity\SourceField;
use Gally\Search\Entity\Facet\Configuration;
use Gally\Search\Repository\Facet\ConfigurationRepository;

/**
 * Class that manage cache for facet configuration entities.
 */
class FacetConfigurationManager
{
    private const CACHE_TAG = 'gally_facet_configurations';

    /**
     * @param ConfigurationRepository $facetConfigRepository facet configuration repository
     * @param CacheManagerInterface   $cacheManager          cache manager
     */
    public function __construct(
        private ConfigurationRepository $facetConfigRepository,
        private CacheManagerInterface $cacheManager,
    ) {
    }

    public function getAllFacetConfigurations(Metadata $metadata, ?string $categoryId): array
    {
        return $this->cacheManager->get(
            self::CACHE_TAG . '_' . $metadata->getEntity() . '_' . $categoryId,
            function (&$tags, &$ttl) use ($metadata, $categoryId) {
                $facetConfigs = [];
                $this->facetConfigRepository->setMetadata($metadata);
                $this->facetConfigRepository->setCategoryId($categoryId);
                foreach ($this->facetConfigRepository->findAll() as $facetConfig) {
                    $facetConfigs[$facetConfig->getSourceField()->getId()] = $facetConfig;
                }

                return $facetConfigs;
            },
            [self::CACHE_TAG]
        );
    }

    public function getOndBySourceField(Metadata $metadata, ?string $categoryId, SourceField $sourceField): Configuration
    {
        $configs = $this->getAllFacetConfigurations($metadata, $categoryId);

        return $configs[$sourceField->getId()];
    }

    public function cleanCache()
    {
        $this->cacheManager->clearTags([self::CACHE_TAG]);
    }
}
