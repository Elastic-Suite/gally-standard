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

namespace Gally\Search\Tests\Unit\Elasticsearch\Adapter\Common\Request\Query\Assembler;

use Gally\Search\Elasticsearch\Adapter\Common\Request\Query\Assembler\SpanNear as SpanNearQueryAssembler;
use Gally\Search\Elasticsearch\Request\Query\SpanNear as SpanNearQuery;
use Gally\Search\Elasticsearch\Request\SpanQueryInterface;

/**
 * Span near search request query test case.
 */
class SpanNearTest extends AbstractComplexQueryAssemblerTestCase
{
    /**
     * Test the assembler with mandatory params only.
     */
    public function testDefaultSpanNearQueryAssembler(): void
    {
        $assembler = $this->getQueryAssembler();

        $spanNearQuery = new SpanNearQuery([$this->getSubQueryMock('sub_span_query_1'), $this->getSubQueryMock('sub_span_query_2')]);
        $query = $assembler->assembleQuery($spanNearQuery);

        $this->assertArrayHasKey('span_near', $query);

        $this->assertArrayHasKey('clauses', $query['span_near']);
        $this->assertArrayHasKey('slop', $query['span_near']);
        $this->assertArrayHasKey('in_order', $query['span_near']);

        $this->assertContains(['sub_span_query_1'], $query['span_near']['clauses']);
        $this->assertContains(['sub_span_query_2'], $query['span_near']['clauses']);
        $this->assertEquals(0, $query['span_near']['slop']);
        $this->assertTrue($query['span_near']['in_order']);
        $this->assertEquals(SpanNearQuery::DEFAULT_BOOST_VALUE, $query['span_near']['boost']);
        $this->assertArrayNotHasKey('_name', $query['span_near']);
    }

    /**
     * Test the assembler with mandatory + name params.
     */
    public function testNamedSpanNearQueryAssembler(): void
    {
        $assembler = $this->getQueryAssembler();

        $boolQuery = new SpanNearQuery([], 10, true, 'queryName', 1);
        $query = $assembler->assembleQuery($boolQuery);

        $this->assertArrayHasKey('_name', $query['span_near']);
        $this->assertEquals('queryName', $query['span_near']['_name']);
    }

    protected function getQueryAssembler(): SpanNearQueryAssembler
    {
        return new SpanNearQueryAssembler($this->getParentQueryAssembler());
    }

    /**
     * Mock a sub query.
     *
     * @param string $queryName Query name
     */
    protected function getSubQueryMock(string $queryName): SpanQueryInterface
    {
        $mock = $this->getMockBuilder(SpanQueryInterface::class)->getMock();
        $mock->method('getName')->willReturn($queryName);

        return $mock;
    }
}
