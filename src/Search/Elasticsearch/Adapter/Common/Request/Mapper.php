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

namespace Gally\Search\Elasticsearch\Adapter\Common\Request;

use Gally\Search\Elasticsearch\Adapter\Common\Request\Aggregation\Assembler as AggregationAssembler;
use Gally\Search\Elasticsearch\Adapter\Common\Request\Query\Assembler as QueryAssembler;
use Gally\Search\Elasticsearch\Adapter\Common\Request\SortOrder\Assembler as SortOrderAssembler;
use Gally\Search\Elasticsearch\RequestInterface;

/**
 * Map a search request into an ES Search query.
 */
class Mapper
{
    private QueryAssembler $queryAssembler;

    private SortOrderAssembler $sortOrderAssembler;

    private AggregationAssembler $aggregationAssembler;

    /**
     * Constructor.
     *
     * @param QueryAssembler       $queryAssembler       Adapter query assembler
     * @param SortOrderAssembler   $sortOrderAssembler   Adapter sort orders assembler
     * @param AggregationAssembler $aggregationAssembler Adapter aggregations assembler
     */
    public function __construct(
        QueryAssembler $queryAssembler,
        SortOrderAssembler $sortOrderAssembler,
        AggregationAssembler $aggregationAssembler
    ) {
        $this->queryAssembler = $queryAssembler;
        $this->sortOrderAssembler = $sortOrderAssembler;
        $this->aggregationAssembler = $aggregationAssembler;
    }

    /**
     * Transform the search request into an ES request.
     *
     * @param RequestInterface $request Search Request
     */
    public function assembleSearchRequest(RequestInterface $request): array
    {
        $searchRequest = [
            'size' => $request->getSize(),
        ];

        if ($searchRequest['size'] > 0) {
            $searchRequest['sort'] = $this->getSortOrders($request);
            $searchRequest['from'] = $request->getFrom();
        }

        $query = $this->getRootQuery($request);
        if ($query) {
            $searchRequest['query'] = $query;
        }

        $filter = $this->getRootFilter($request);
        if ($filter) {
            $searchRequest['post_filter'] = $filter;
        }

        $aggregations = $this->getAggregations($request);
        if (!empty($aggregations)) {
            $searchRequest['aggregations'] = $aggregations;
        }

        $searchRequest['track_total_hits'] = $request->getTrackTotalHits();

        return $searchRequest;
    }

    /**
     * Extract and assemble the root query of the search request.
     *
     * @param RequestInterface $request Search request
     */
    private function getRootQuery(RequestInterface $request): array
    {
        return $this->queryAssembler->assembleQuery($request->getQuery());
    }

    /*
     * Extract and assemble the root filter of the search request.
     *
     * @param RequestInterface $request Search request
     */
    private function getRootFilter(RequestInterface $request): ?array
    {
        $filter = null;

        if ($request->getFilter()) {
            $filter = $this->queryAssembler->assembleQuery($request->getFilter());
        }

        return $filter;
    }

    /**
     * Extract and assemble sort orders of the search request.
     *
     * @param RequestInterface $request Search request
     */
    private function getSortOrders(RequestInterface $request): array
    {
        $sortOrders = [];

        if ($request->getSortOrders()) {
            $sortOrders = $this->sortOrderAssembler->assembleSortOrders($request->getSortOrders());
        }

        return $sortOrders;
    }

    /*
     * Extract and assemble aggregations of the search request.
     *
     * @param RequestInterface $request Search request
     */
    private function getAggregations(RequestInterface $request): array
    {
        $aggregations = [];

        if ($request->getAggregations()) {
            $aggregations = $this->aggregationAssembler->assembleAggregations($request->getAggregations());
        }

        return $aggregations;
    }
}
