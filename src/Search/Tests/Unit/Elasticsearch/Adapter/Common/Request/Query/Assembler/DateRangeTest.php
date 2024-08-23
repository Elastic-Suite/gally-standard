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

use Gally\Search\Elasticsearch\Adapter\Common\Request\Query\Assembler\DateRange as DateRangeQueryAssembler;
use Gally\Search\Elasticsearch\Request\Query\DateRange as DateRangeQuery;
use Gally\Search\Elasticsearch\Request\QueryInterface;

/**
 * Range search request query test case.
 */
class DateRangeTest extends AbstractSimpleQueryAssemblerTestCase
{
    /**
     * Test the assembler with mandatory params only.
     */
    public function testAnonymousRangeQueryAssembler(): void
    {
        $assembler = $this->getQueryAssembler();

        $rangeQuery = new DateRangeQuery('field', ['bounds']);
        $query = $assembler->assembleQuery($rangeQuery);

        $this->assertArrayHasKey('range', $query);
        $this->assertArrayHasKey('field', $query['range']);
        $this->assertEquals(
            [
                'bounds',
                'boost' => DateRangeQuery::DEFAULT_BOOST_VALUE,
                'format' => 'yyyy-MM-dd',
            ],
            $query['range']['field']
        );

        $this->assertArrayNotHasKey('_name', $query['range']);
    }

    /**
     * Test the assembler with mandatory + name params.
     */
    public function testNamedRangeQueryAssembler(): void
    {
        $assembler = $this->getQueryAssembler();

        $rangeQuery = new DateRangeQuery('field', ['bounds'], 'queryName');
        $query = $assembler->assembleQuery($rangeQuery);

        $this->assertArrayHasKey('_name', $query['range']);
        $this->assertEquals('queryName', $query['range']['_name']);
    }

    /**
     * Test the assembler with mandatory + name params.
     */
    public function testRangeQueryAssemblerWithFormat(): void
    {
        $assembler = $this->getQueryAssembler();

        $rangeQuery = new DateRangeQuery('field', ['bounds'], null, QueryInterface::DEFAULT_BOOST_VALUE, 'yyyy-MM');
        $query = $assembler->assembleQuery($rangeQuery);

        $this->assertEquals('yyyy-MM', $query['range']['field']['format']);
    }

    protected function getQueryAssembler(): DateRangeQueryAssembler
    {
        return new DateRangeQueryAssembler();
    }
}
