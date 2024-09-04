<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @package   Gally
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Gally\Search\Tests\Unit\Elasticsearch\Adapter\Common\Request\Query\Assembler;

use Gally\Search\Elasticsearch\Adapter\Common\Request\Query\Assembler\SpanTerm as SpanTermQueryAssembler;
use Gally\Search\Elasticsearch\Request\Query\SpanTerm as SpanTermQuery;

/**
 * Span term search request query test case.
 */
class SpanTermTest extends AbstractSimpleQueryAssemblerTest
{
    /**
     * Test the assembler with mandatory params only.
     */
    public function testAnonymousSpanTermQueryAssembler(): void
    {
        $assembler = $this->getQueryAssembler();

        $termQuery = new SpanTermQuery('value', 'field');
        $query = $assembler->assembleQuery($termQuery);

        $this->assertArrayHasKey('span_term', $query);
        $this->assertArrayHasKey('field', $query['span_term']);
        $this->assertEquals('value', $query['span_term']['field']['value']);
        $this->assertEquals(SpanTermQuery::DEFAULT_BOOST_VALUE, $query['span_term']['field']['boost']);

        $this->assertArrayNotHasKey('_name', $query['span_term']);
    }

    /**
     * Test the assembler with mandatory + name params.
     */
    public function testNamedTermQueryAssembler(): void
    {
        $assembler = $this->getQueryAssembler();

        $termQuery = new SpanTermQuery('value', 'field', 'queryName');
        $query = $assembler->assembleQuery($termQuery);

        $this->assertArrayHasKey('_name', $query['span_term']);
        $this->assertEquals('queryName', $query['span_term']['_name']);
    }

    /**
     * {@inheritDoc}
     */
    protected function getQueryAssembler(): SpanTermQueryAssembler
    {
        return new SpanTermQueryAssembler();
    }
}
