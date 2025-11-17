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

namespace Gally\Search\Tests\Unit\Elasticsearch\Builder\Request\SortOrder;

use Gally\Search\Elasticsearch\Builder\Request\SortOrder\GeoDistance;
use Gally\Search\Elasticsearch\Request\SortOrderInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GeoDistanceTest extends KernelTestCase
{
    /**
     * @dataProvider geoDistanceSortOrderDataProvider
     */
    public function testGeoDistanceSortOrder(
        string $field,
        string $referenceLocation,
        ?string $direction,
        ?string $unit,
        ?string $mode,
        ?string $distanceType,
        ?bool $ignoreUnmapped,
        ?string $name,
        string $expectedReferenceLocation,
        string $expectedDirection,
        string $expectedUnit,
        string $expectedMode,
        string $expectedDistanceType,
        bool $expectedIgnoreUnmapped,
    ): void {
        $params = [
            'field' => $field,
            'referenceLocation' => $referenceLocation,
            'direction' => $direction,
            'unit' => $unit,
            'mode' => $mode,
            'distanceType' => $distanceType,
            'ignoreUnmapped' => $ignoreUnmapped,
            'name' => $name,
        ];
        $params = array_filter($params);
        $sortOrder = new GeoDistance(...$params); // @phpstan-ignore-line

        $this->assertEquals(SortOrderInterface::TYPE_DISTANCE, $sortOrder->getType());
        $this->assertEquals($expectedReferenceLocation, $sortOrder->getReferenceLocation());
        $this->assertEquals($expectedDirection, $sortOrder->getDirection());
        $this->assertEquals($expectedUnit, $sortOrder->getUnit());
        $this->assertEquals($expectedMode, $sortOrder->getMode());
        $this->assertEquals($expectedDistanceType, $sortOrder->getDistanceType());
        $this->assertEquals($expectedIgnoreUnmapped, $sortOrder->getIgnoreUnmapped());
    }

    protected function geoDistanceSortOrderDataProvider(): array
    {
        return [
            [
                'myLocation', // field,
                '12,3456 -12,3456', // referenceLocation,
                null, // direction,
                null, // unit,
                null, // mode,
                null, // distanceType,
                null, // ignoreUnmapped,
                null, // $name,
                '12,3456 -12,3456', // expected referenceLocation,
                SortOrderInterface::SORT_ASC, // expected direction,
                'km', // expected unit,
                'min', // expected mode,
                'arc', // expected distanceType,
                false, // expected ignore unmapped,
            ],
            [
                'myLocation', // field,
                '12,3456 12,3456', // referenceLocation,
                SortOrderInterface::SORT_DESC, // direction,
                'm', // unit,
                'avg', // mode,
                'plane', // distanceType,
                true, // ignoreUnmapped,
                null, // $name,
                '12,3456 12,3456', // expected referenceLocation,
                SortOrderInterface::SORT_DESC, // expected direction,
                'm', // expected unit,
                'avg', // expected mode,
                'plane', // expected distanceType,
                true, // expected ignore unmapped,
            ],
        ];
    }
}
