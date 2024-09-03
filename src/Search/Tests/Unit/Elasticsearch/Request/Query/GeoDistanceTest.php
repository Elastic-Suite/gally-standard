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

namespace Gally\Search\Tests\Unit\Elasticsearch\Request\Query;

use Gally\Search\Elasticsearch\Request\Query\GeoDistance;
use Gally\Search\Elasticsearch\Request\QueryFactory;
use Gally\Search\Elasticsearch\Request\QueryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GeoDistanceTest extends KernelTestCase
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
        $this->expectException(\ArgumentCountError::class);
        self::$queryFactory->create(QueryInterface::TYPE_GEO_DISTANCE);
    }

    /**
     * @dataProvider geoDistanceDataProvider
     *
     * @param string      $field             Query field
     * @param string      $distance          Maximum distance
     * @param string      $referenceLocation Reference location
     * @param string|null $name              Query name
     * @param float|null  $boost             Query boost
     */
    public function testCreateComplexParams(
        string $field,
        string $distance,
        string $referenceLocation,
        ?string $name = null,
        ?float $boost = null,
    ): void {
        // TODO: use reflection to build mapping ?
        $queryParams = array_filter([
            'field' => $field,
            'distance' => $distance,
            'referenceLocation' => $referenceLocation,
            'name' => $name,
            'boost' => $boost,
        ]);

        /** @var GeoDistance $query */
        $query = self::$queryFactory->create(QueryInterface::TYPE_GEO_DISTANCE, $queryParams);

        // Testing types.
        $this->doStructureTest($query);

        // Testing provided values.
        $this->assertEquals($field, $query->getField());
        $this->assertEquals($distance, $query->getDistance());
        $this->assertEquals($referenceLocation, $query->getReferenceLocation());
        if ($name) {
            $this->assertEquals($name, $query->getName());
        }
        if ($boost) {
            $this->assertEquals($boost, $query->getBoost());
        }
    }

    public function geoDistanceDataProvider(): array
    {
        return [
            [
                'manufacture_location',
                '12.456 -12.456',
                '5km',
            ],
            [
                'manufacture_location',
                '12.456 -12.456',
                '10km',
                null,
                null,
            ],
            [
                'manufacture_location',
                '12.456 -43.21',
                '10km',
                null,
                10,
            ],
            [
                'manufacture_location',
                '12.456 -43.21',
                '10km',
                'test query name',
                10,
            ],
        ];
    }

    private function doStructureTest(mixed $query): void
    {
        $this->assertInstanceOf(QueryInterface::class, $query);
        $this->assertInstanceOf(GeoDistance::class, $query);
        $this->assertEquals(QueryInterface::TYPE_GEO_DISTANCE, $query->getType());
        if ($query->getName()) {
            $this->assertIsString($query->getName());
        }
        $this->assertIsFloat($query->getBoost());

        /** @var GeoDistance $query */
        $this->assertIsString($query->getField());
        $this->assertIsString($query->getDistance());
        $this->assertIsString($query->getReferenceLocation());
    }
}
