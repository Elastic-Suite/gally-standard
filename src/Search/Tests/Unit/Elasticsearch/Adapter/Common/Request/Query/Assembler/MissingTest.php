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

use Gally\Search\Elasticsearch\Adapter\Common\Request\Query\Assembler\Missing as MissingQueryAssembler;
use Gally\Search\Elasticsearch\Request\Query\Missing as MissingQuery;

/**
 * Missing search request query test case.
 */
class MissingTest extends AbstractSimpleQueryAssemblerTestCase
{
    /**
     * Test the assembler with mandatory params only.
     */
    public function testAnonymousMissingQueryAssembler(): void
    {
        $assembler = $this->getQueryAssembler();

        $missingQuery = new MissingQuery('field');
        $query = $assembler->assembleQuery($missingQuery);

        $this->assertArrayHasKey('bool', $query);
        $this->assertArrayHasKey('must_not', $query['bool']);
        $this->assertArrayHasKey('exists', $query['bool']['must_not']);
        $this->assertArrayHasKey('field', $query['bool']['must_not']['exists']);
        $this->assertArrayNotHasKey('_name', $query['bool']);
    }

    /**
     * Test the assembler with mandatory + name params.
     */
    public function testNamedMissingQueryAssembler(): void
    {
        $assembler = $this->getQueryAssembler();

        $missingQuery = new MissingQuery('field', 'queryName');
        $query = $assembler->assembleQuery($missingQuery);

        $this->assertArrayHasKey('_name', $query['bool']);
        $this->assertEquals('queryName', $query['bool']['_name']);
    }

    protected function getQueryAssembler(): MissingQueryAssembler
    {
        return new MissingQueryAssembler();
    }
}
