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

namespace Gally\Search\Tests\Unit\Elasticsearch\Adapter\Common\Request\Aggregation\Assembler\Pipeline;

use Gally\Search\Elasticsearch\Adapter\Common\Request\Aggregation\Assembler\Pipeline\MaxBucket as MaxBucketAssembler;
use Gally\Search\Elasticsearch\Request\Aggregation\Pipeline\MaxBucket;
use Gally\Search\Elasticsearch\Request\PipelineInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Search adapter max bucket aggregation assembler test case.
 */
class MaxBucketTest extends KernelTestCase
{
    /**
     * Build a bucket selector aggregation from a bucket.
     */
    public function testBasicAggregationBuild(): void
    {
        $aggBuilder = new MaxBucketAssembler();
        $pipeline = new MaxBucket('aggregationName', 'bucket.path');

        $aggregation = $aggBuilder->assembleAggregation($pipeline);

        $this->assertArrayHasKey('max_bucket', $aggregation);
        $this->assertEquals('bucket.path', $aggregation['max_bucket']['buckets_path']);
        $this->assertEquals('', $aggregation['max_bucket']['format']);
        $this->assertEquals(PipelineInterface::GAP_POLICY_SKIP, $aggregation['max_bucket']['gap_policy']);
    }

    /**
     * Build a bucket selector aggregation from a bucket.
     */
    public function testComplexeAggregationBuild(): void
    {
        $aggBuilder = new MaxBucketAssembler();
        $pipeline = new MaxBucket('aggregationName', 'bucket.path', PipelineInterface::GAP_POLICY_INSERT_ZEROS, 'testFormat');

        $aggregation = $aggBuilder->assembleAggregation($pipeline);

        $this->assertArrayHasKey('max_bucket', $aggregation);
        $this->assertEquals('bucket.path', $aggregation['max_bucket']['buckets_path']);
        $this->assertEquals('testFormat', $aggregation['max_bucket']['format']);
        $this->assertEquals(PipelineInterface::GAP_POLICY_INSERT_ZEROS, $aggregation['max_bucket']['gap_policy']);
    }

    /**
     * Test an exception is thrown when using the term aggs builder with another bucket type.
     */
    public function testInvalidBucketAggregationBuild(): void
    {
        $aggBuilder = new MaxBucketAssembler();
        $this->expectExceptionMessage('Aggregation assembler : invalid pipeline type invalidType.');
        $this->expectException(\InvalidArgumentException::class);
        $pipeline = $this->getMockBuilder(PipelineInterface::class)->getMock();
        $pipeline->method('getType')->willReturn('invalidType');

        $aggBuilder->assembleAggregation($pipeline);
    }
}
