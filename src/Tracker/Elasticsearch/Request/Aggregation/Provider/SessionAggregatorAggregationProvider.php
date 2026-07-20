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
use Gally\Search\Elasticsearch\Request\QueryFactory;
use Gally\Search\Elasticsearch\Request\QueryInterface;

/**
 * Aggregates tracking_event documents into the tracking_session shape (one bucket per session_uid,
 * with array fields grouped by metadata_code: views/cart/order/searches), via a live _search --
 * used to validate the aggregation before it gets ported to an OpenSearch Transform.
 *
 * session_uid/session_vid are flat (no dot in the code, so no nested type in the mapping): an
 * OpenSearch Transform cannot group_by (or natively aggregate) a nested field, so the session
 * grouping key itself has to stay flat -- see TrackingEvent::getData(). search_query and
 * product_list remain first-level nested objects (Gally nests the first dotted segment of any
 * source_field code): reaching product_list.item_count from within the search_query nested scope
 * still needs an explicit reverseNestedBucket, there is no other example of this in the codebase
 * yet, so double-check the generated query and its response shape live once the stack is up.
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
        $eventTypeFilter = fn (string $eventType) => $this->queryFactory->create(
            QueryInterface::TYPE_TERM,
            ['field' => 'event_type', 'value' => $eventType]
        );

        // "items" sub-bucket shared by views/cart/order: the distinct entity codes (product or
        // category ids) touched by that event type, grouped by metadata_code in the parent bucket.
        $itemsAggregation = [
            'items' => [
                'name' => 'items',
                'type' => BucketInterface::TYPE_TERMS,
                'field' => 'entity_code',
                'size' => 10000,
                'sortOrder' => BucketInterface::SORT_ORDER_COUNT,
            ],
        ];

        $groupedByMetadataCode = fn (string $name, string $eventType) => [
            'name' => $name,
            'type' => BucketInterface::TYPE_TERMS,
            'field' => 'metadata_code',
            'size' => 10,
            'sortOrder' => BucketInterface::SORT_ORDER_COUNT,
            'filter' => $eventTypeFilter($eventType),
            'childAggregations' => $itemsAggregation,
        ];

        return [
            'session_id' => [
                'name' => 'session_id',
                'type' => BucketInterface::TYPE_TERMS,
                'field' => 'session_uid',
                'size' => 10000,
                'sortOrder' => BucketInterface::SORT_ORDER_COUNT,
                'childAggregations' => [
                    // Only the top (most frequent) bucket is kept -- these are expected to be
                    // constant for the whole session, size:1 is enough and cheaper than 10000.
                    'visitor_id' => [
                        'name' => 'visitor_id',
                        'type' => BucketInterface::TYPE_TERMS,
                        'field' => 'session_vid',
                        'size' => 1,
                        'sortOrder' => BucketInterface::SORT_ORDER_COUNT,
                    ],
                    'group_id' => [
                        'name' => 'group_id',
                        'type' => BucketInterface::TYPE_TERMS,
                        'field' => 'group_id',
                        'size' => 1,
                        'sortOrder' => BucketInterface::SORT_ORDER_COUNT,
                    ],
                    'views' => $groupedByMetadataCode('views', 'view'),
                    'cart' => $groupedByMetadataCode('cart', 'add_to_cart'),
                    'order' => $groupedByMetadataCode('order', 'order'),
                    // category_sale (categories of sold items): no source data in tracking_event
                    // today (Gally's "order" event only carries entity_code of the sold product,
                    // not its categories) -- omitted.
                    'searches' => [
                        'name' => 'searches',
                        'type' => BucketInterface::TYPE_TERMS,
                        'field' => 'search_query.query_text.sortable',
                        'nestedPath' => 'search_query',
                        'size' => 10000,
                        'sortOrder' => BucketInterface::SORT_ORDER_COUNT,
                        'filter' => $eventTypeFilter('search'),
                        'childAggregations' => [
                            // results_count lives under product_list, a sibling nested path
                            // of search_query: escape back to root first.
                            'search_query_root' => [
                                'name' => 'search_query_root',
                                'type' => BucketInterface::TYPE_REVERSE_NESTED,
                                'field' => 'search_query.query_text.sortable',
                                'childAggregations' => [
                                    'results_count' => [
                                        'name' => 'results_count',
                                        'type' => MetricInterface::TYPE_SUM,
                                        'field' => 'product_list.item_count',
                                        'nestedPath' => 'product_list',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'start_time' => [
                        'name' => 'start_time',
                        'type' => MetricInterface::TYPE_MIN,
                        'field' => '@timestamp',
                    ],
                    'end_time' => [
                        'name' => 'end_time',
                        'type' => MetricInterface::TYPE_MAX,
                        'field' => '@timestamp',
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
