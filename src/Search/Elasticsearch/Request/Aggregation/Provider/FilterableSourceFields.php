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

namespace Gally\Search\Elasticsearch\Request\Aggregation\Provider;

use Gally\Search\Elasticsearch\Request\Aggregation\ConfigResolver\FieldAggregationConfigResolverInterface;
use Gally\Search\Elasticsearch\Request\Aggregation\Modifier\ModifierInterface;
use Gally\Search\Elasticsearch\Request\BucketInterface;
use Gally\Search\Elasticsearch\Request\ContainerConfigurationInterface;
use Gally\Search\Entity\Facet\Configuration;
use Gally\Search\Repository\Facet\ConfigurationRepository;
use Gally\Search\Service\SearchContext;

/**
 * Aggregations Provider based on source fields.
 */
class FilterableSourceFields implements AggregationProviderInterface
{
    /**
     * @param ConfigurationRepository                   $facetConfigRepository facet configuration repository
     * @param SearchContext                             $searchContext         Search context
     * @param FieldAggregationConfigResolverInterface[] $aggregationResolvers  attributes Aggregation Resolver Pool
     * @param ModifierInterface[]                       $modifiersPool         product Attributes modifiers
     */
    public function __construct(
        private ConfigurationRepository $facetConfigRepository,
        private SearchContext $searchContext,
        private iterable $aggregationResolvers,
        private iterable $modifiersPool = []
    ) {
    }

    public function getAggregations(
        ContainerConfigurationInterface $containerConfig,
        $query = null,
        $filters = [],
        $queryFilters = []
    ): array {
        $currentCategory = $this->searchContext->getCategory();
        $this->facetConfigRepository->setCategoryId($currentCategory?->getId());
        $this->facetConfigRepository->setMetadata($containerConfig->getMetadata());
        $facetConfigs = $this->facetConfigRepository->findAll();

        foreach ($this->modifiersPool as $modifier) {
            $facetConfigs = $modifier->modifyFacetConfigs($containerConfig, $facetConfigs, $query, $filters, $queryFilters);
        }

        $aggregations = $this->getAggregationsConfig($containerConfig, $facetConfigs);

        foreach ($this->modifiersPool as $modifier) {
            $aggregations = $modifier->modifyAggregations($containerConfig, $aggregations, $query, $filters, $queryFilters);
        }

        return $aggregations;
    }

    public function useFacetConfiguration(): bool
    {
        return true;
    }

    /**
     * Get aggregations config.
     *
     * @param Configuration[] $facetConfigs the source fields facet configuration
     */
    private function getAggregationsConfig(ContainerConfigurationInterface $containerConfig, array $facetConfigs): array
    {
        $aggregations = [];

        foreach ($facetConfigs as $facetConfig) {
            $aggregationConfig = $this->getAggregationConfig($facetConfig, $containerConfig);
            if (!empty($aggregationConfig) && isset($aggregationConfig['name'])) {
                $aggregations[$aggregationConfig['name']] = $aggregationConfig;
            }
        }

        return $aggregations;
    }

    private function getAggregationConfig(Configuration $facetConfig, ContainerConfigurationInterface $containerConfig): array
    {
        $config = [
            'name' => $facetConfig->getSourceField()->getCode(),
            'type' => BucketInterface::TYPE_TERMS,
        ];

        foreach ($this->aggregationResolvers as $aggregationResolver) {
            if ($aggregationResolver->supports($facetConfig->getSourceField())) {
                $config = $aggregationResolver->getConfig($containerConfig, $facetConfig->getSourceField());
                break;
            }
        }

        $config['sortOrder'] = $facetConfig->getSortOrder();
        $config['size'] = \in_array(
            $facetConfig->getSortOrder(),
            [
                BucketInterface::SORT_ORDER_MANUAL,
                BucketInterface::SORT_ORDER_TERM_DESC,
                BucketInterface::SORT_ORDER_NATURAL_ASC,
                BucketInterface::SORT_ORDER_NATURAL_DESC,
            ], true
        ) ? 0 : $facetConfig->getMaxSize();

        return $config;
    }
}
