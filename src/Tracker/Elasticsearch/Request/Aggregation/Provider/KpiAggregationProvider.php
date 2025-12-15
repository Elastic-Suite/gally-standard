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

class KpiAggregationProvider implements AggregationProviderInterface
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
        return [
            'count_by_event' => [
                'name' => 'count_by_event',
                'type' => BucketInterface::TYPE_TERMS,
                'field' => 'event_type',
                'childAggregations' => [
                    'count_by_metadata' => [
                        'name' => 'count_by_metadata',
                        'type' => BucketInterface::TYPE_TERMS,
                        'field' => 'metadata_code',
                    ],
                ],
            ],
            'visitor_count' => [
                'name' => 'visitor_count',
                'type' => MetricInterface::TYPE_CARDINALITY,
                'field' => 'session.vid',
                'nestedPath' => 'session', // Todo fix this
            ],
            'order_count' => [
                'name' => 'order_count',
                'type' => MetricInterface::TYPE_CARDINALITY,
                'field' => 'order_id.keyword',
            ],
        ];
    }

    public function useFacetConfiguration(): bool
    {
        return false;
    }
}
