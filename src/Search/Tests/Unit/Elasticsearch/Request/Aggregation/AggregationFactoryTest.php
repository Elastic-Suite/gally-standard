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

namespace Gally\Search\Tests\Unit\Elasticsearch\Request\Aggregation;

use Gally\DependencyInjection\GenericFactory;
use Gally\Search\Elasticsearch\Request\AggregationFactory;
use Gally\Search\Elasticsearch\Request\AggregationInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AggregationFactoryTest extends KernelTestCase
{
    /**
     * Test the aggregation creation using the factory.
     */
    public function testAggregationCreate(): void
    {
        $aggregation = $this->getAggregationFactory()->create('fakeAggregationType', []);
        $this->assertInstanceOf(AggregationInterface::class, $aggregation);
    }

    /**
     * Test submitting an invalid aggregation type throws an exception.
     */
    public function testInvalidAggregationCreate(): void
    {
        $this->expectExceptionMessage('No factory found for aggregation of type invalidAggregationType');
        $this->expectException(\LogicException::class);
        $this->getAggregationFactory()->create('invalidAggregationType', []);
    }

    /**
     * Prepared a mocked aggregation factory.
     */
    private function getAggregationFactory(): AggregationFactory
    {
        $aggregationMock = $this->getMockBuilder(AggregationInterface::class)->getMock();
        $aggregationFactoryMock = $this->getMockBuilder(GenericFactory::class)
            ->onlyMethods(['create'])
            ->getMock();

        $aggregationFactoryMock->method('create')->willReturn($aggregationMock);

        $factories = ['fakeAggregationType' => $aggregationFactoryMock];

        return new AggregationFactory($factories);
    }
}
