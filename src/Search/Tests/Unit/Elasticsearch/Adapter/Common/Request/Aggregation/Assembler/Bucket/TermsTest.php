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

use Gally\Search\Elasticsearch\Adapter\Common\Request\Aggregation\Assembler\Bucket\Terms as TermsAssembler;
use Gally\Search\Elasticsearch\Request\Aggregation\Bucket\Terms;
use Gally\Search\Elasticsearch\Request\BucketInterface;
use Gally\Search\Elasticsearch\Request\QueryInterface;
use Gally\Search\Elasticsearch\Request\SortOrderInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Search adapter term aggregation assembler test case.
 */
class TermsTest extends KernelTestCase
{
    /**
     * Test the standard term aggregation building.
     */
    public function testBasicTermAggregationBuild(): void
    {
        $aggBuilder = $this->getAggregationAssembler();
        $termsBucket = new Terms('aggregationName', 'fieldName');

        $aggregation = $aggBuilder->assembleAggregation($termsBucket);

        $this->assertArrayHasKey('terms', $aggregation);
        $this->assertEquals('fieldName', $aggregation['terms']['field']);
        $this->assertEquals(BucketInterface::MAX_BUCKET_SIZE, $aggregation['terms']['size']);
        $this->assertEquals([BucketInterface::SORT_ORDER_COUNT => SortOrderInterface::SORT_DESC], $aggregation['terms']['order']);
    }

    /**
     * Test the standard term aggregation assembling sorted by alphabetic order.
     */
    public function testAlphabeticSortOrderTermAggregationBuild(): void
    {
        $aggBuilder = $this->getAggregationAssembler();
        $termsBucket = new Terms('aggregationName', 'fieldName', [], null, null, null, 0, BucketInterface::SORT_ORDER_TERM, [], [], 1);

        $aggregation = $aggBuilder->assembleAggregation($termsBucket);

        $this->assertArrayHasKey('terms', $aggregation);
        $this->assertEquals('fieldName', $aggregation['terms']['field']);
        $this->assertEquals([BucketInterface::SORT_ORDER_TERM => SortOrderInterface::SORT_ASC], $aggregation['terms']['order']);
    }

    /**
     * Test the standard term aggregation assembling sorted by a custom sort order.
     */
    public function testCustomSortOrderTermAggregationBuild(): void
    {
        $aggBuilder = $this->getAggregationAssembler();
        $sortOrder = [BucketInterface::SORT_ORDER_TERM => SortOrderInterface::SORT_DESC];
        $termsBucket = new Terms('aggregationName', 'fieldName', [], null, null, null, 0, $sortOrder, [], [], 1);

        $aggregation = $aggBuilder->assembleAggregation($termsBucket);

        $this->assertArrayHasKey('terms', $aggregation);
        $this->assertEquals('fieldName', $aggregation['terms']['field']);
        $this->assertEquals([BucketInterface::SORT_ORDER_TERM => SortOrderInterface::SORT_DESC], $aggregation['terms']['order']);
    }

    /**
     * Test the standard term aggregation building with filter.
     */
    public function testWithFilter(): void
    {
        $filter = $this->getMockBuilder(QueryInterface::class)->getMock();
        $filter->method('getName')->willReturn('filter1');

        $aggBuilder = $this->getAggregationAssembler();
        $termsBucket = new Terms(
            'aggregationName',
            'fieldName',
            [],
            null,
            $filter
        );

        $aggregation = $aggBuilder->assembleAggregation($termsBucket);

        $this->assertArrayHasKey('terms', $aggregation);
        $this->assertEquals('fieldName', $aggregation['terms']['field']);
        $this->assertEquals([BucketInterface::SORT_ORDER_COUNT => SortOrderInterface::SORT_DESC], $aggregation['terms']['order']);
    }

    /**
     * Test an exception is thrown when using the term aggs builder with another bucket type.
     */
    public function testInvalidBucketAggregationBuild(): void
    {
        $this->expectExceptionMessage('Aggregation assembler : invalid aggregation type invalidType.');
        $this->expectException(\InvalidArgumentException::class);
        $termsBucket = $this->getMockBuilder(BucketInterface::class)->getMock();
        $termsBucket->method('getType')->willReturn('invalidType');

        $this->getAggregationAssembler()->assembleAggregation($termsBucket);
    }

    /**
     * Test the max bucket size limitation.
     *
     * @dataProvider sizeDataProvider
     *
     * @param int $size     configured bucket size
     * @param int $expected expected bucket size in the built aggregation
     */
    public function testBucketSize(int $size, int $expected): void
    {
        $aggBuilder = $this->getAggregationAssembler();
        $termsBucket = new Terms('aggregationName', 'fieldName', [], null, null, null, $size);

        $aggregation = $aggBuilder->assembleAggregation($termsBucket);

        $this->assertEquals($expected, $aggregation['terms']['size']);
    }

    /**
     * Dataset used to run testBucketSize.
     */
    public function sizeDataProvider(): array
    {
        return [
            [0, BucketInterface::MAX_BUCKET_SIZE],
            [BucketInterface::MAX_BUCKET_SIZE - 1, BucketInterface::MAX_BUCKET_SIZE - 1],
            [BucketInterface::MAX_BUCKET_SIZE, BucketInterface::MAX_BUCKET_SIZE],
            [BucketInterface::MAX_BUCKET_SIZE + 1, BucketInterface::MAX_BUCKET_SIZE],
        ];
    }

    /**
     * Aggregation assembler used in tests.
     */
    private function getAggregationAssembler(): TermsAssembler
    {
        return new TermsAssembler();
    }
}
