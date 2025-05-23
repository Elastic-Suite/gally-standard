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

namespace Gally\Search\Elasticsearch\Builder\Request;

use Gally\Search\Elasticsearch\Builder\Request\Aggregation\AggregationBuilder;
use Gally\Search\Elasticsearch\Builder\Request\Query\QueryBuilder;
use Gally\Search\Elasticsearch\Builder\Request\SortOrder\SortOrderBuilder;
use Gally\Search\Elasticsearch\Request\ContainerConfigurationInterface;
use Gally\Search\Elasticsearch\Request\QueryInterface;
use Gally\Search\Elasticsearch\RequestFactoryInterface;
use Gally\Search\Elasticsearch\RequestInterface;
use Gally\Search\Elasticsearch\Spellchecker;
use Gally\Search\Elasticsearch\SpellcheckerInterface;

class SimpleRequestBuilder
{
    /**
     * Constructor.
     *
     * @param RequestFactoryInterface              $requestFactory           Factory used to build the reques
     * @param QueryBuilder                         $queryBuilder             Builder for the query part of the request
     * @param SortOrderBuilder                     $sortOrderBuilder         Builder for the sort order(s) part of the request
     * @param AggregationBuilder                   $aggregationBuilder       Builder for the aggregation part of the request
     * @param Spellchecker\RequestFactoryInterface $spellcheckRequestFactory Spellchecker request factory
     * @param SpellcheckerInterface                $spellchecker             Spellchecker Spellchecker
     */
    public function __construct(
        private RequestFactoryInterface $requestFactory,
        private QueryBuilder $queryBuilder,
        private SortOrderBuilder $sortOrderBuilder,
        private AggregationBuilder $aggregationBuilder,
        private Spellchecker\RequestFactoryInterface $spellcheckRequestFactory,
        private SpellcheckerInterface $spellchecker,
    ) {
    }

    /**
     * Create a new search request.
     *
     * @param ContainerConfigurationInterface $containerConfig Search request configuration
     * @param int                             $from            Search request pagination from clause
     * @param int                             $size            Search request pagination size
     * @param string|QueryInterface|null      $query           Search request query
     * @param array                           $sortOrders      Search request sort orders
     * @param array                           $filters         Search request filters
     * @param QueryInterface[]                $queryFilters    Search request filters prebuilt as QueryInterface
     * @param ?array                          $facets          Search request facets
     */
    public function create(
        ContainerConfigurationInterface $containerConfig,
        int $from,
        int $size,
        string|QueryInterface|null $query = null,
        array $sortOrders = [],
        array $filters = [],
        array $queryFilters = [],
        ?array $facets = []
    ): RequestInterface {
        $facets = \is_array($facets)
            ? array_merge($facets, $containerConfig->getAggregations($query, $filters, $queryFilters))
            : [];
        $facetFilters = array_intersect_key($filters, $facets);
        $queryFilters = array_merge($queryFilters, array_diff_key($filters, $facetFilters));

        $spellingType = SpellcheckerInterface::SPELLING_TYPE_EXACT;

        if ($query && \is_string($query)) {
            $spellingType = $this->getSpellingType($containerConfig, $query);
        }

        $requestParams = [
            'name' => $containerConfig->getName(),
            'indexName' => $containerConfig->getIndexName(),
            'from' => $from,
            'size' => $size,
            'query' => $this->queryBuilder->createQuery($containerConfig, $query, $queryFilters, $spellingType),
            'sortOrders' => $this->sortOrderBuilder->buildSortOrders($containerConfig, $sortOrders),
            'aggregations' => $this->aggregationBuilder->buildAggregations($containerConfig, $facets, $facetFilters),
            'spellingType' => $spellingType,
            'trackTotalHits' => $containerConfig->getTrackTotalHits(),
        ];

        if (!empty($facetFilters)) {
            $requestParams['filter'] = $this->queryBuilder->createFilterQuery($containerConfig, $facetFilters);
        }

        return $this->requestFactory->create($requestParams);
    }

    /*
     * Retrieve the spelling type for a fulltext query.
     *
     * @param ContainerConfigurationInterface $containerConfig Search request configuration
     * @param string                          $queryText       Query text
     */
    private function getSpellingType(ContainerConfigurationInterface $containerConfig, string $queryText): int
    {
        $spellcheckRequestParams = [
            'indexName' => $containerConfig->getIndexName(),
            'queryText' => $queryText,
            'cutoffFrequency' => $containerConfig->getRelevanceConfig()->getCutOffFrequency(),
        ];

        $spellcheckRequest = $this->spellcheckRequestFactory->create($spellcheckRequestParams);

        return $this->spellchecker->getSpellingType($spellcheckRequest);
    }
}
