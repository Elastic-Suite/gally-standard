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

namespace Gally\Tracker\Elasticsearch\Request\Aggregation\Provider;

use Gally\Search\Elasticsearch\Request\Aggregation\Provider\AggregationProviderInterface;
use Gally\Search\Elasticsearch\Request\BucketInterface;
use Gally\Search\Elasticsearch\Request\ContainerConfigurationInterface;
use Gally\Search\Elasticsearch\Request\MetricInterface;
use Gally\Search\Elasticsearch\Request\PipelineInterface;
use Gally\Search\Elasticsearch\Request\QueryFactory;
use Gally\Search\Elasticsearch\Request\QueryInterface;

/**
 * Reproduces (as a live _search, not persisted) the ElasticSuite session_aggregator query on top
 * of the tracking_event mapping.
 *
 * tracking_event nests "session", "search_query" and "product_list" as three SEPARATE first-level
 * nested objects (Gally nests the first dotted segment of any source_field code, unlike ElasticSuite
 * which groups everything under one shared "page" nested object). Because of that, moving from one
 * nested scope to another (or back to the root document) requires an explicit reverseNestedBucket --
 * there is no other example of this in the codebase yet, so double-check the generated query via
 * gally:mapping:get / _search once the stack is up.
 */
class SessionAggregatorAggregationProvider implements AggregationProviderInterface
{
    public function __construct(
        private QueryFactory $queryFactory,
    ) {
    }

    public function getAggregations(
        ContainerConfigurationInterface $containerConfig,
        QueryInterface|string|null $query = null,
        array $filters = [],
        array $queryFilters = [],
    ): array {
        $viewFilter = fn (string $metadataCode) => $this->queryFactory->create(QueryInterface::TYPE_BOOL, [
            'must' => [
                $this->queryFactory->create(QueryInterface::TYPE_TERM, ['field' => 'event_type', 'value' => 'view']),
                $this->queryFactory->create(QueryInterface::TYPE_TERM, ['field' => 'metadata_code', 'value' => $metadataCode]),
            ],
        ]);

        $cartFilter = $this->queryFactory->create(QueryInterface::TYPE_BOOL, [
            'must' => [
                $this->queryFactory->create(QueryInterface::TYPE_TERM, ['field' => 'event_type', 'value' => 'add_to_cart']),
                $this->queryFactory->create(QueryInterface::TYPE_TERM, ['field' => 'metadata_code', 'value' => 'product']),
            ],
        ]);

        $saleFilter = $this->queryFactory->create(QueryInterface::TYPE_BOOL, [
            'must' => [
                $this->queryFactory->create(QueryInterface::TYPE_TERM, ['field' => 'event_type', 'value' => 'order']),
                $this->queryFactory->create(QueryInterface::TYPE_TERM, ['field' => 'metadata_code', 'value' => 'product']),
            ],
        ]);

        $searchFilter = $this->queryFactory->create(QueryInterface::TYPE_TERM, ['field' => 'event_type', 'value' => 'search']);

        // NB: this filter is evaluated OUTSIDE the search_query nested scope (Assembler.php wraps
        // getFilter() around the nested aggregation, not inside it), so the product_list conditions
        // below must go through their own nestedQuery to be reachable from the root document.
        $searchQueryVoidFilter = $this->queryFactory->create(QueryInterface::TYPE_BOOL, [
            'must' => [
                $searchFilter,
                $this->queryFactory->create(QueryInterface::TYPE_NESTED, [
                    'path' => 'product_list',
                    'query' => $this->queryFactory->create(QueryInterface::TYPE_TERM, ['field' => 'product_list.item_count', 'value' => 0]),
                ]),
                $this->queryFactory->create(QueryInterface::TYPE_NESTED, [
                    'path' => 'product_list',
                    'query' => $this->queryFactory->create(QueryInterface::TYPE_TERM, ['field' => 'product_list.current_page', 'value' => 1]),
                ]),
            ],
            'mustNot' => [
                $this->queryFactory->create(QueryInterface::TYPE_NESTED, [
                    'path' => 'product_list',
                    'query' => $this->queryFactory->create(QueryInterface::TYPE_EXISTS, ['field' => 'product_list.filters.name']),
                ]),
            ],
        ]);

        return [
            'session_id' => [
                'name' => 'session_id',
                'type' => BucketInterface::TYPE_TERMS,
                'field' => 'session.uid',
                'nestedPath' => 'session',
                'size' => 10000,
                'sortOrder' => BucketInterface::SORT_ORDER_COUNT,
                'childAggregations' => [
                    // Escape the "session" nested scope: everything below (root fields, or fields
                    // nested under a DIFFERENT path) is not reachable directly from here.
                    'session_root' => [
                        'name' => 'session_root',
                        'type' => BucketInterface::TYPE_REVERSE_NESTED,
                        'field' => 'session.uid', // required by AbstractBucket, unused by this bucket type
                        'childAggregations' => [
                            'visitor_id' => [
                                'name' => 'visitor_id',
                                'type' => BucketInterface::TYPE_TERMS,
                                'field' => 'session.vid',
                                'nestedPath' => 'session',
                                'size' => 10000,
                                'sortOrder' => BucketInterface::SORT_ORDER_COUNT,
                            ],
                            'customer_group_id' => [
                                'name' => 'customer_group_id',
                                'type' => BucketInterface::TYPE_TERMS,
                                'field' => 'group_id',
                                'size' => 10000,
                                'sortOrder' => BucketInterface::SORT_ORDER_COUNT,
                            ],
                            // customer_company_id: no equivalent field in tracking_event today, omitted.
                            'product_view' => [
                                'name' => 'product_view',
                                'type' => BucketInterface::TYPE_TERMS,
                                'field' => 'entity_code',
                                'size' => 10000,
                                'sortOrder' => BucketInterface::SORT_ORDER_COUNT,
                                'filter' => $viewFilter('product'),
                            ],
                            'category_view' => [
                                'name' => 'category_view',
                                'type' => BucketInterface::TYPE_TERMS,
                                'field' => 'entity_code',
                                'size' => 10000,
                                'sortOrder' => BucketInterface::SORT_ORDER_COUNT,
                                'filter' => $viewFilter('category'),
                            ],
                            'product_cart' => [
                                'name' => 'product_cart',
                                'type' => BucketInterface::TYPE_TERMS,
                                'field' => 'entity_code',
                                'size' => 10000,
                                'sortOrder' => BucketInterface::SORT_ORDER_COUNT,
                                'filter' => $cartFilter,
                            ],
                            'product_sale' => [
                                'name' => 'product_sale',
                                'type' => BucketInterface::TYPE_TERMS,
                                'field' => 'entity_code',
                                'size' => 10000,
                                'sortOrder' => BucketInterface::SORT_ORDER_COUNT,
                                'filter' => $saleFilter,
                            ],
                            // category_sale: the "order" event has no category_ids of the sold item
                            // in tracking_event today, omitted.
                            'search_query' => [
                                'name' => 'search_query',
                                'type' => BucketInterface::TYPE_TERMS,
                                'field' => 'search_query.query_text.sortable',
                                'nestedPath' => 'search_query',
                                'size' => 10000,
                                'sortOrder' => BucketInterface::SORT_ORDER_COUNT,
                                'filter' => $searchFilter,
                                'childAggregations' => [
                                    // search_result_count lives under product_list, a sibling nested
                                    // path of search_query: escape back to root first.
                                    'search_query_root' => [
                                        'name' => 'search_query_root',
                                        'type' => BucketInterface::TYPE_REVERSE_NESTED,
                                        'field' => 'search_query.query_text.sortable',
                                        'childAggregations' => [
                                            'search_result_count' => [
                                                'name' => 'search_result_count',
                                                'type' => MetricInterface::TYPE_SUM,
                                                'field' => 'product_list.item_count',
                                                'nestedPath' => 'product_list',
                                            ],
                                            'query_with_results' => [
                                                'name' => 'query_with_results',
                                                'type' => PipelineInterface::TYPE_BUCKET_SELECTOR,
                                                // search_result_count is itself wrapped in a nested(product_list)
                                                // aggregation with the same name one level down -- verify this
                                                // buckets_path live once the stack is up, it is untested.
                                                'bucketsPath' => ['search_result_count' => 'search_result_count>search_result_count'],
                                                'script' => 'params.search_result_count > 0',
                                                'gapPolicy' => PipelineInterface::GAP_POLICY_SKIP,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'search_query_void' => [
                                'name' => 'search_query_void',
                                'type' => BucketInterface::TYPE_TERMS,
                                'field' => 'search_query.query_text.sortable',
                                'nestedPath' => 'search_query',
                                'size' => 10000,
                                'sortOrder' => BucketInterface::SORT_ORDER_COUNT,
                                'filter' => $searchQueryVoidFilter,
                            ],
                            'start_date' => [
                                'name' => 'start_date',
                                'type' => MetricInterface::TYPE_MIN,
                                'field' => '@timestamp',
                            ],
                            'end_date' => [
                                'name' => 'end_date',
                                'type' => MetricInterface::TYPE_MAX,
                                'field' => '@timestamp',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function useFacetConfiguration(): bool
    {
        return false;
    }
}
