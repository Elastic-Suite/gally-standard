<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @package   Elasticsuite
 * @author    ElasticSuite Team <elasticsuite@smile.fr>
 * @copyright 2022 Smile
 * @license   Licensed to Smile-SA. All rights reserved. No warranty, explicit or implicit, provided.
 *            Unauthorized copying of this file, via any medium, is strictly prohibited.
 */

declare(strict_types=1);

namespace Elasticsuite\Search\Tests\Unit\Elasticsearch\Request\Query;

use ArgumentCountError;
use Elasticsuite\Search\Elasticsearch\Request\Query\Exists as ExistsQuery;
use Elasticsuite\Search\Elasticsearch\Request\QueryFactory;
use Elasticsuite\Search\Elasticsearch\Request\QueryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ExistsQueryTest extends KernelTestCase
{
    private static QueryFactory $queryFactory;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        \assert(static::getContainer()->get(QueryFactory::class) instanceof QueryFactory);
        self::$queryFactory = static::getContainer()->get(QueryFactory::class);
    }

    public function testFailedCreate(): void
    {
        $this->expectException(ArgumentCountError::class);
        $existsQuery = self::$queryFactory->create(QueryInterface::TYPE_EXISTS);
    }

    public function testDefaultCreate(): void
    {
        $existsQuery = self::$queryFactory->create(QueryInterface::TYPE_EXISTS, [
            'field' => 'foo',
        ]);
        // Testing types.
        $this->assertInstanceOf(QueryInterface::class, $existsQuery);
        $this->assertInstanceOf(ExistsQuery::class, $existsQuery);
        $this->doStructureTest($existsQuery);

        // Testing default values.
        $this->assertEquals('foo', $existsQuery->getField());
        $this->assertNull($existsQuery->getName());
    }

    /**
     * @dataProvider existsQueryDataProvider
     *
     * @param string  $field Field to test existence of
     * @param ?string $name  Query name
     * @param int     $boost Query boost
     */
    public function testCreate(
        string $field,
        ?string $name,
        int $boost
    ): void {
        $this->performCreateParamsTests($field, $name, $boost);
    }

    public function existsQueryDataProvider(): array
    {
        return [
            [
                'foo',
                'does foo exists',
                1,
            ],
            [
                'bar',
                'does bar exists',
                2,
            ],
        ];
    }

    private function doStructureTest(mixed $existsQuery): void
    {
        /** @var ExistsQuery $existsQuery */
        $this->assertEquals(QueryInterface::TYPE_EXISTS, $existsQuery->getType());
        $this->assertIsString($existsQuery->getField());
        $this->assertIsInt($existsQuery->getBoost());
    }

    /**
     * @param string  $field Field to test existence of
     * @param ?string $name  Query name
     * @param int     $boost Query boost
     */
    private function performCreateParamsTests(
        string $field,
        ?string $name,
        int $boost
    ): void {
        // TODO: use reflection to build mapping ?
        $queryParams = [
            'field' => $field,
            'name' => $name,
            'boost' => $boost,
        ];
        $queryParams = array_filter(
            $queryParams,
            function ($param) {
                return null !== $param && \strlen((string) $param);
            }
        );
        $existsQuery = self::$queryFactory->create(
            QueryInterface::TYPE_EXISTS,
            $queryParams
        );

        // Testing types.
        $this->assertInstanceOf(QueryInterface::class, $existsQuery);
        $this->assertInstanceOf(ExistsQuery::class, $existsQuery);
        $this->doStructureTest($existsQuery);

        // Testing provided values.
        $this->assertEquals($field, $existsQuery->getField());
        $this->assertEquals($name, $existsQuery->getName());
        $this->assertEquals($boost, $existsQuery->getBoost());
    }
}