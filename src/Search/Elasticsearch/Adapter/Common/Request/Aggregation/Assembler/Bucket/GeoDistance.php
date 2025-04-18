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

namespace Gally\Search\Elasticsearch\Adapter\Common\Request\Aggregation\Assembler\Bucket;

use Gally\Search\Elasticsearch\Adapter\Common\Request\Aggregation\AssemblerInterface;
use Gally\Search\Elasticsearch\Request\Aggregation\Bucket\GeoDistance as GeoDistanceBucket;
use Gally\Search\Elasticsearch\Request\AggregationInterface;
use Gally\Search\Elasticsearch\Request\BucketInterface;

/**
 * Assemble an ES geo distance aggregation.
 */
class GeoDistance implements AssemblerInterface
{
    public function assembleAggregation(AggregationInterface $aggregation): array
    {
        if (BucketInterface::TYPE_GEO_DISTANCE !== $aggregation->getType()) {
            throw new \InvalidArgumentException("Aggregation assembler : invalid aggregation type {$aggregation->getType()}.");
        }

        /** @var GeoDistanceBucket $aggregation */
        $aggParams = [
            'field' => $aggregation->getField(),
            'origin' => $aggregation->getOrigin(),
            'unit' => $aggregation->getUnit(),
            'ranges' => $aggregation->getRanges(),
        ];

        return ['geo_distance' => $aggParams];
    }
}
