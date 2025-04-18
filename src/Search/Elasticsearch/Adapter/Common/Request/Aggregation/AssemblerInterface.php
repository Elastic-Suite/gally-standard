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

namespace Gally\Search\Elasticsearch\Adapter\Common\Request\Aggregation;

use Gally\Search\Elasticsearch\Request\AggregationInterface;

/**
 * Assemble Elasticsearch aggregations from search request AggregationInterface queries.
 */
interface AssemblerInterface
{
    /**
     * Assemble the concrete Elasticsearch aggregation from an Aggregation object.
     *
     * @param AggregationInterface $aggregation aggregation to be assembled
     */
    public function assembleAggregation(AggregationInterface $aggregation): array;
}
