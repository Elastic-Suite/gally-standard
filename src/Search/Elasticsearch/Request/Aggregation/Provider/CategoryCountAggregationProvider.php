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

use Gally\Search\Elasticsearch\Request\BucketInterface;
use Gally\Search\Elasticsearch\Request\ContainerConfigurationInterface;
use Gally\Search\Elasticsearch\Request\QueryInterface;

/**
 * Category count request aggregation resolver.
 */
class CategoryCountAggregationProvider implements AggregationProviderInterface
{
    public function getAggregations(
        ContainerConfigurationInterface $containerConfig,
        QueryInterface|string|null $query = null,
        array $filters = [],
        array $queryFilters = []
    ): array {
        return [
            [
                'name' => 'category.id',
                'type' => BucketInterface::TYPE_TERMS,
                'size' => 10000,
            ],
        ];
    }

    public function useFacetConfiguration(): bool
    {
        return false;
    }
}
