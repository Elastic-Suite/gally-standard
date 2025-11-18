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

use Gally\Search\Elasticsearch\Adapter\Common\Request\Aggregation\Assembler\Bucket\GeoDistance as GeoDistanceAssembler;
use Gally\Search\Elasticsearch\Request\Aggregation\Bucket\GeoDistance;
use Gally\Search\Elasticsearch\Request\BucketInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Search adapter date histogram aggregation assembler test case.
 */
class GeoDistanceTest extends KernelTestCase
{
    /**
     * Build a histogram aggregation from a bucket.
     */
    public function testBasicAggregationBuild(): void
    {
        $aggBuilder = new GeoDistanceAssembler();
        $bucket = new GeoDistance('aggregationName', 'fieldName', '123,45 54,321', [['to' => 100]]);

        $aggregation = $aggBuilder->assembleAggregation($bucket);

        $this->assertArrayHasKey('geo_distance', $aggregation);
        $this->assertEquals('fieldName', $aggregation['geo_distance']['field']);
        $this->assertEquals('123,45 54,321', $aggregation['geo_distance']['origin']);
        $this->assertEquals('km', $aggregation['geo_distance']['unit']);
        $this->assertEquals([['to' => 100]], $aggregation['geo_distance']['ranges']);
    }

    /**
     * Test an exception is thrown when using the geo distance aggs builder invalid types.
     */
    public function testInvalidBucketAggregationBuild(): void
    {
        $aggBuilder = new GeoDistanceAssembler();
        $this->expectExceptionMessage('Aggregation assembler : invalid aggregation type invalidType.');
        $this->expectException(\InvalidArgumentException::class);
        $termsBucket = $this->getMockBuilder(BucketInterface::class)->getMock();
        $termsBucket->method('getType')->willReturn('invalidType');

        $aggBuilder->assembleAggregation($termsBucket);
    }

    /**
     * Test an exception is thrown when using the geo distance aggs builder invalid ranges.
     */
    public function testInvalidBucketAggregationRanges(): void
    {
        $this->expectExceptionMessage('Invalid geo distance aggregation range.');
        $this->expectException(\InvalidArgumentException::class);
        new GeoDistance('aggregationName', 'fieldName', '123,45 54,321', [['invalid' => 100]], 'km');
    }
}
