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

namespace Gally\Search\Tests\Unit\Elasticsearch\Adapter\Common\Request\Aggregation\Assembler\Bucket;

use Gally\Search\Elasticsearch\Adapter\Common\Request\Aggregation\Assembler\Bucket\DateHistogram as DateHistogramAssembler;
use Gally\Search\Elasticsearch\Request\Aggregation\Bucket\DateHistogram;
use Gally\Search\Elasticsearch\Request\BucketInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Search adapter date histogram aggregation assembler test case.
 */
class DateHistogramTest extends KernelTestCase
{
    /**
     * Build a histogram aggregation from a bucket.
     */
    public function testBasicAggregationBuild(): void
    {
        $aggBuilder = new DateHistogramAssembler();
        $bucket = new DateHistogram('aggregationName', 'fieldName');

        $aggregation = $aggBuilder->assembleAggregation($bucket);

        $this->assertArrayHasKey('date_histogram', $aggregation);
        $this->assertEquals('fieldName', $aggregation['date_histogram']['field']);
        $this->assertEquals('1d', $aggregation['date_histogram']['interval']);
        $this->assertEquals(0, $aggregation['date_histogram']['min_doc_count']);
        $this->assertEquals('yyyy-MM-dd', $aggregation['date_histogram']['format']);
    }

    /**
     * Build a histogram aggregation from a bucket.
     */
    public function testComplexeAggregationBuild(): void
    {
        $aggBuilder = new DateHistogramAssembler();
        $bucket = new DateHistogram('aggregationName', 'fieldName', [], null, null, null, '2y', 10, 'yyyy-MM');

        $aggregation = $aggBuilder->assembleAggregation($bucket);

        $this->assertArrayHasKey('date_histogram', $aggregation);
        $this->assertEquals('fieldName', $aggregation['date_histogram']['field']);
        $this->assertEquals('2y', $aggregation['date_histogram']['interval']);
        $this->assertEquals(10, $aggregation['date_histogram']['min_doc_count']);
        $this->assertEquals('yyyy-MM', $aggregation['date_histogram']['format']);
    }

    /**
     * Test an exception is thrown when using the term aggs builder with another bucket type.
     */
    public function testInvalidBucketAggregationBuild(): void
    {
        $aggBuilder = new DateHistogramAssembler();
        $this->expectExceptionMessage('Aggregation assembler : invalid aggregation type invalidType.');
        $this->expectException(\InvalidArgumentException::class);
        $termsBucket = $this->getMockBuilder(BucketInterface::class)->getMock();
        $termsBucket->method('getType')->willReturn('invalidType');

        $aggBuilder->assembleAggregation($termsBucket);
    }
}
