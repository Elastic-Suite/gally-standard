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
use Gally\Search\Elasticsearch\Request\AggregationInterface;
use Gally\Search\Elasticsearch\Request\BucketInterface;

/**
 * Assemble Es Reverse nested aggregation.
 */
class ReverseNested implements AssemblerInterface
{
    public function assembleAggregation(AggregationInterface $aggregation): array
    {
        if (BucketInterface::TYPE_REVERSE_NESTED !== $aggregation->getType()) {
            throw new \InvalidArgumentException("Aggregation assembler : invalid aggregation type {$aggregation->getType()}.");
        }

        return ['reverse_nested' => new \stdClass()];
    }
}
