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

use Gally\Search\Elasticsearch\Adapter\Common\Request\Query\Assembler\GeoDistance as GeoDistanceQueryAssembler;
use Gally\Search\Elasticsearch\Request\Query\GeoDistance as GeoDistanceQuery;

/**
 * Range search request query test case.
 */
class GeoDistanceTest extends AbstractSimpleQueryAssemblerTestCase
{
    /**
     * Test the assembler with mandatory params only.
     */
    public function testAnonymousRangeQueryAssembler(): void
    {
        $assembler = $this->getQueryAssembler();

        $geoDistanceQuery = new GeoDistanceQuery('location_field', '42km', '12.123 -12.132');
        $query = $assembler->assembleQuery($geoDistanceQuery);

        $this->assertArrayHasKey('geo_distance', $query);
        $this->assertEquals(
            [
                'distance' => '42km',
                'location_field' => '12.123 -12.132',
                'distance_type' => 'arc',
                'validation_method' => 'STRICT',
                'boost' => GeoDistanceQuery::DEFAULT_BOOST_VALUE,
            ],
            $query['geo_distance']
        );

        $this->assertArrayNotHasKey('_name', $query['geo_distance']);
    }

    /**
     * Test the assembler with mandatory + name params.
     */
    public function testComplexeGeoDistanceQueryAssembler(): void
    {
        $assembler = $this->getQueryAssembler();

        $geoDistanceQuery = new GeoDistanceQuery(
            'location_field',
            '42km',
            '12.123 -12.132',
            GeoDistanceQuery::DISTANCE_TYPE_PLANE,
            GeoDistanceQuery::VALIDATION_METHOD_IGNORE_MALFORMED,
            'test_query_name'
        );
        $query = $assembler->assembleQuery($geoDistanceQuery);

        $this->assertArrayHasKey('_name', $query['geo_distance']);
        $this->assertEquals('test_query_name', $query['geo_distance']['_name']);

        $this->assertArrayHasKey('geo_distance', $query);
        $this->assertEquals(
            [
                'distance' => '42km',
                'location_field' => '12.123 -12.132',
                'distance_type' => 'plane',
                'validation_method' => 'IGNORE_MALFORMED',
                'boost' => GeoDistanceQuery::DEFAULT_BOOST_VALUE,
                '_name' => 'test_query_name',
            ],
            $query['geo_distance']
        );
    }

    protected function getQueryAssembler(): GeoDistanceQueryAssembler
    {
        return new GeoDistanceQueryAssembler();
    }
}
