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

namespace Gally\Search\Tests\Unit\Elasticsearch\Adapter\Common\Request;

use Gally\Search\Elasticsearch\Adapter\Common\Request\Aggregation\Assembler as AggregationAssembler;
use Gally\Search\Elasticsearch\Adapter\Common\Request\Mapper;
use Gally\Search\Elasticsearch\Adapter\Common\Request\Query\Assembler as QueryAssembler;
use Gally\Search\Elasticsearch\Adapter\Common\Request\SortOrder\Assembler as SortOrderAssembler;
use Gally\Search\Elasticsearch\Request;
use Gally\Search\Elasticsearch\Request\QueryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Search adapter query mapper test case.
 */
class MapperTest extends KernelTestCase
{
    /**
     * Test mapping a base query.
     */
    public function testBaseQueryMapping(): void
    {
        $mapper = $this->getMapper();
        $query = $this->getMockBuilder(QueryInterface::class)->getMock();
        $searchRequest = new Request('requestName', 'indexName', $query, null, null, 0, 1);

        $mappedRequest = $mapper->assembleSearchRequest($searchRequest);

        $this->assertEquals(0, $mappedRequest['from']);
        $this->assertEquals(1, $mappedRequest['size']);
        $this->assertEquals([], $mappedRequest['sort']);
        $this->assertEquals(['mockQuery'], $mappedRequest['query']);
    }

    /**
     * Test mapping a query using a filter.
     */
    public function testFilteredQueryMapping(): void
    {
        $mapper = $this->getMapper();
        $query = $this->getMockBuilder(QueryInterface::class)->getMock();
        $filter = $this->getMockBuilder(QueryInterface::class)->getMock();
        $searchRequest = new Request('requestName', 'indexName', $query, $filter);

        $mappedRequest = $mapper->assembleSearchRequest($searchRequest);

        $this->assertEquals(['mockQuery'], $mappedRequest['post_filter']);
    }

    /**
     * Test aggregations mapping.
     */
    public function testAggregationsMapping(): void
    {
        $mapper = $this->getMapper();
        $query = $this->getMockBuilder(QueryInterface::class)->getMock();
        $aggs = $this->getMockBuilder(Request\AggregationInterface::class)->getMock();

        $searchRequest = new Request('requestName', 'indexName', $query, null, null, 0, 1, [$aggs]);

        $mappedRequest = $mapper->assembleSearchRequest($searchRequest);

        $this->assertEquals(['aggregations'], $mappedRequest['aggregations']);
    }

    /**
     * Test sort orders mapping.
     */
    public function testSortOrdersMapping(): void
    {
        $mapper = $this->getMapper();
        $query = $this->getMockBuilder(QueryInterface::class)->getMock();
        $sortOrder = $this->getMockBuilder(Request\SortOrderInterface::class)->getMock();

        $searchRequest = new Request('requestName', 'indexName', $query, null, [$sortOrder], 0, 10);

        $mappedRequest = $mapper->assembleSearchRequest($searchRequest);

        $this->assertEquals(['sortOrders'], $mappedRequest['sort']);
    }

    /**
     * Prepare the search request mapper used during tests.
     */
    private function getMapper(): Mapper
    {
        $queryAssemblerMock = $this->getMockBuilder(QueryAssembler::class)->disableOriginalConstructor()->getMock();
        $queryAssemblerMock->method('assembleQuery')->willReturn(['mockQuery']);

        $sortOrderAssemblerMock = $this->getMockBuilder(SortOrderAssembler::class)->disableOriginalConstructor()->getMock();
        $sortOrderAssemblerMock->method('assembleSortOrders')->willReturn(['sortOrders']);

        $aggregationAssemblerMock = $this->getMockBuilder(AggregationAssembler::class)->disableOriginalConstructor()->getMock();
        $aggregationAssemblerMock->method('assembleAggregations')->willReturn(['aggregations']);

        return new Mapper($queryAssemblerMock, $sortOrderAssemblerMock, $aggregationAssemblerMock);
    }
}
