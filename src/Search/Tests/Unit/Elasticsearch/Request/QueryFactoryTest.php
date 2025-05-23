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

namespace Gally\Search\Tests\Unit\Elasticsearch\Request;

use Gally\DependencyInjection\GenericFactory;
use Gally\Search\Elasticsearch\Request\QueryFactory;
use Gally\Search\Elasticsearch\Request\QueryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class QueryFactoryTest extends KernelTestCase
{
    /**
     * Test the query creation using the factory.
     */
    public function testQueryCreate(): void
    {
        $query = $this->getQueryFactory()->create('queryType', []);
        $this->assertInstanceOf(QueryInterface::class, $query);
    }

    /**
     * Test submitting an invalid query type throws an exception.
     */
    public function testInvalidQueryCreate(): void
    {
        $this->expectExceptionMessage('No factory found for query of type invalidQueryType');
        $this->expectException(\LogicException::class);
        $this->getQueryFactory()->create('invalidQueryType', []);
    }

    /**
     * Prepared a mocked query factory.
     */
    private function getQueryFactory(): QueryFactory
    {
        $queryMock = $this->getMockBuilder(QueryInterface::class)->getMock();
        $queryFactoryMock = $this->getMockBuilder(GenericFactory::class)
            ->onlyMethods(['create'])
            ->getMock();

        $queryFactoryMock->method('create')
            ->willReturn($queryMock);

        $factories = ['queryType' => $queryFactoryMock];

        return new QueryFactory($factories);
    }
}
