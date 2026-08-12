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

use Gally\Cache\Service\CacheManagerInterface;
use Gally\Metadata\Entity\SourceField;
use Gally\Metadata\Repository\SourceFieldOptionRepository;
use Gally\Metadata\Service\MetadataSourceFieldProviderCache;
use Gally\Search\Elasticsearch\Adapter\Common\Response\AggregationInterface;
use Gally\Search\Elasticsearch\Request\Aggregation\ConfigResolver\FieldAggregationConfigResolverInterface;
use Gally\Search\Elasticsearch\Request\Aggregation\Modifier\ModifierInterface;
use Gally\Search\Elasticsearch\Request\BucketInterface;
use Gally\Search\Elasticsearch\Request\ContainerConfigurationInterface;
use Gally\Search\Entity\Facet\Configuration;
use Gally\Search\Repository\Facet\ConfigurationRepository;
use Gally\Search\Service\AggregationOptionsFormatter;
use Gally\Search\Service\SearchContext;
use Gally\Search\Service\ViewMoreContext;

/**
 * Aggregations Provider based on source fields.
 */
class FilterableSourceFields implements AggregationProviderInterface
{
    public const CACHE_TAG_FACET_CONFIG = 'gally_facet_configuration';

    /**
     * @param ConfigurationRepository                   $facetConfigRepository facet configuration repository
     * @param SearchContext                             $searchContext         Search context
     * @param FieldAggregationConfigResolverInterface[] $aggregationResolvers  attributes Aggregation Resolver Pool
     * @param ModifierInterface[]                       $modifiersPool         product Attributes modifiers
     */
    public function __construct(
        private ConfigurationRepository $facetConfigRepository,
        private SearchContext $searchContext,
        private CacheManagerInterface $cacheManager,
        private iterable $aggregationResolvers,
        private iterable $modifiersPool,
        private SourceFieldOptionRepository $sourceFieldOptionRepository,
        private ViewMoreContext $viewMoreContext,
        private AggregationOptionsFormatter $aggregationOptionsFormatter,
    ) {
    }

    public function getAggregations(
        ContainerConfigurationInterface $containerConfig,
        $query = null,
        $filters = [],
        $queryFilters = [],
    ): array {
        $currentCategory = $this->searchContext->getCategory();

        $cacheKey = \sprintf(
            'gally_facet_configuration_%s_%s',
            $containerConfig->getMetadata()->getEntity(),
            $currentCategory?->getId() ?? 'null',
        );

        $facetConfigs = $this->cacheManager->get(
            $cacheKey,
            function (&$tags, &$ttl) use ($currentCategory, $containerConfig): array {
                $this->facetConfigRepository->setCategoryId($currentCategory?->getId());
                $this->facetConfigRepository->setMetadata($containerConfig->getMetadata());

                return $this->facetConfigRepository->findAll();
            },
            [self::CACHE_TAG_FACET_CONFIG, MetadataSourceFieldProviderCache::getEntityTag($containerConfig->getMetadata()->getEntity())],
        );

        foreach ($this->modifiersPool as $modifier) {
            $facetConfigs = $modifier->modifyFacetConfigs($containerConfig, $facetConfigs, $query, $filters, $queryFilters);
        }

        $aggregations = $this->getAggregationsConfig($containerConfig, $facetConfigs);

        foreach ($this->modifiersPool as $modifier) {
            $aggregations = $modifier->modifyAggregations($containerConfig, $aggregations, $query, $filters, $queryFilters);
        }

        return $aggregations;
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
        $config['booleanLogic'] = $facetConfig->getBooleanLogic();
        // Manual/natural/term_desc sort orders are applied app-side on the full option set,
        // so the ES query must not pre-truncate by count before that sort runs.
        $config['size'] = \in_array($facetConfig->getSortOrder(), [
            BucketInterface::SORT_ORDER_MANUAL,
            BucketInterface::SORT_ORDER_TERM_DESC,
            BucketInterface::SORT_ORDER_NATURAL_ASC,
            BucketInterface::SORT_ORDER_NATURAL_DESC,
        ], true) ? 0 : $facetConfig->getMaxSize();

        return $config;
    }

    /**
     * Format aggregation response data for API output.
     * Handles option building, category label fetching and sorting.
     */
    public function formatAggregationOptions(
        AggregationInterface $aggregation,
        SourceField $sourceField,
        ContainerConfigurationInterface $containerConfig,
    ): array {
        $options = $this->aggregationOptionsFormatter->format($aggregation, $sourceField, $containerConfig);

        if (empty($options)) {
            return $options;
        }

        $this->facetConfigRepository->setMetadata($containerConfig->getMetadata());
        $facetConfig = $this->facetConfigRepository->findOndBySourceField($sourceField);

        return $this->applySortAndMaxSize($options, $facetConfig, $sourceField);
    }

    /**
     * Apply sorting and maxSize limit to aggregation options.
     */
    private function applySortAndMaxSize(array $options, ?Configuration $facetConfig, SourceField $sourceField): array
    {
        if (null === $facetConfig || empty($options)) {
            return $options;
        }

        $sortOrder = $facetConfig->getSortOrder();

        // Only apply sorting for specific sort orders
        if (\in_array($sortOrder, [
            BucketInterface::SORT_ORDER_MANUAL,
            BucketInterface::SORT_ORDER_TERM_DESC,
            BucketInterface::SORT_ORDER_NATURAL_ASC,
            BucketInterface::SORT_ORDER_NATURAL_DESC,
        ], true)) {
            $sourceFieldOptions = $this->sourceFieldOptionRepository->findBy(['sourceField' => $sourceField]);
            $sourceFieldOptionsByCode = array_combine(
                array_map(fn ($option) => $option->getCode(), $sourceFieldOptions),
                $sourceFieldOptions
            );

            $callback = match ($sortOrder) {
                BucketInterface::SORT_ORDER_MANUAL => function ($itemA, $itemB) use ($sourceFieldOptionsByCode) {
                    $itemAPos = isset($sourceFieldOptionsByCode[$itemA['value']])
                        ? $sourceFieldOptionsByCode[$itemA['value']]->getPosition()
                        : 1;
                    $itemBPos = isset($sourceFieldOptionsByCode[$itemB['value']])
                        ? $sourceFieldOptionsByCode[$itemB['value']]->getPosition()
                        : 1;

                    return $itemAPos - $itemBPos;
                },
                BucketInterface::SORT_ORDER_TERM_DESC => function ($itemA, $itemB) {
                    return strcmp($itemB['label'], $itemA['label']);
                },
                BucketInterface::SORT_ORDER_NATURAL_ASC => function ($itemA, $itemB) {
                    return strnatcasecmp($itemA['label'], $itemB['label']);
                },
                BucketInterface::SORT_ORDER_NATURAL_DESC => function ($itemA, $itemB) {
                    return strnatcasecmp($itemB['label'], $itemA['label']);
                },
            };

            usort($options, $callback);
        }

        // Apply maxSize limit (skip in viewMore mode)
        $maxSize = $facetConfig->getMaxSize();
        if (!$this->viewMoreContext->getFilterName() && $maxSize > 0 && \count($options) > $maxSize) {
            $options = \array_slice($options, 0, $maxSize);
        }

        return $options;
    }
}
