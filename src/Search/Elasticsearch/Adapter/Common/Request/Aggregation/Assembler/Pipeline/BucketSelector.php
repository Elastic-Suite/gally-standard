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

namespace Gally\Search\Elasticsearch\Adapter\Common\Request\Aggregation\Assembler\Pipeline;

use Gally\Search\Elasticsearch\Adapter\Common\Request\Aggregation\AssemblerInterface;
use Gally\Search\Elasticsearch\Request\Aggregation\Pipeline\BucketSelector as BucketSelectPipeline;
use Gally\Search\Elasticsearch\Request\AggregationInterface;
use Gally\Search\Elasticsearch\Request\PipelineInterface;

/**
 * Assemble a bucket selector ES pipeline aggregation.
 */
class BucketSelector implements AssemblerInterface
{
    public function assembleAggregation(AggregationInterface $aggregation): array
    {
        if (PipelineInterface::TYPE_BUCKET_SELECTOR !== $aggregation->getType()) {
            throw new \InvalidArgumentException("Aggregation assembler : invalid pipeline type {$aggregation->getType()}.");
        }

        /** @var BucketSelectPipeline $aggregation */
        $aggParams = [
            'buckets_path' => $aggregation->getBucketsPath(),
            'script' => $aggregation->getScript(),
            'gap_policy' => $aggregation->getGapPolicy(),
        ];

        return ['bucket_selector' => $aggParams];
    }
}
