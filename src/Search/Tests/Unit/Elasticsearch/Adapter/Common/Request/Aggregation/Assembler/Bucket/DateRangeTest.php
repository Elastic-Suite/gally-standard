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

use Gally\Search\Elasticsearch\Adapter\Common\Request\Aggregation\Assembler\Bucket\DateRange as DateRangeAssembler;
use Gally\Search\Elasticsearch\Request\Aggregation\Bucket\DateRange;
use Gally\Search\Elasticsearch\Request\BucketInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Search adapter date range aggregation assembler test case.
 */
class DateRangeTest extends KernelTestCase
{
    /**
     * Build a date range aggregation from a bucket.
     */
    public function testBasicAggregationBuild(): void
    {
        $aggBuilder = new DateRangeAssembler();
        $bucket = new DateRange('aggregationName', 'fieldName', []);

        $aggregation = $aggBuilder->assembleAggregation($bucket);

        $this->assertArrayHasKey('date_range', $aggregation);
        $this->assertEquals('fieldName', $aggregation['date_range']['field']);
        $this->assertEquals([], $aggregation['date_range']['ranges']);
        $this->assertEquals('yyyy-MM-dd', $aggregation['date_range']['format']);
    }

    /**
     * Build a date range aggregation from a bucket.
     */
    public function testComplexeAggregationBuild(): void
    {
        $aggBuilder = new DateRangeAssembler();
        $bucket = new DateRange(
            'aggregationName',
            'fieldName',
            [
                ['to' => 'now-10M/M'],
                ['from' => 'now-10M/M'],
            ],
            'yyyy-MM'
        );

        $aggregation = $aggBuilder->assembleAggregation($bucket);

        $this->assertArrayHasKey('date_range', $aggregation);
        $this->assertEquals('fieldName', $aggregation['date_range']['field']);
        $this->assertEquals([['to' => 'now-10M/M'], ['from' => 'now-10M/M']], $aggregation['date_range']['ranges']);
        $this->assertEquals('yyyy-MM', $aggregation['date_range']['format']);
    }

    /**
     * Test an exception is thrown when using the term aggs builder with another bucket type.
     */
    public function testInvalidBucketAggregationBuild(): void
    {
        $aggBuilder = new DateRangeAssembler();
        $this->expectExceptionMessage('Aggregation assembler : invalid aggregation type invalidType.');
        $this->expectException(\InvalidArgumentException::class);
        $termsBucket = $this->getMockBuilder(BucketInterface::class)->getMock();
        $termsBucket->method('getType')->willReturn('invalidType');

        $aggBuilder->assembleAggregation($termsBucket);
    }
}
