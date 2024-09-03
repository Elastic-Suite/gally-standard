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

namespace Gally\Search\Tests\Unit\Elasticsearch\Request\Aggregation\Bucket;

use Gally\Search\Elasticsearch\Request\Aggregation\Bucket\GeoDistance;
use Gally\Search\Elasticsearch\Request\BucketInterface;
use Gally\Search\Elasticsearch\Request\QueryFactory;
use Gally\Search\Elasticsearch\Request\QueryInterface;

class GeoDistanceTest extends AbstractBucketTestCase
{
    public function testFailedCreate(): void
    {
        $this->expectException(\ArgumentCountError::class);
        self::$aggregationFactory->create(BucketInterface::TYPE_GEO_DISTANCE);
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testDefaultCreate(array $params): void
    {
        /** @var GeoDistance $bucket */
        $bucket = self::$aggregationFactory->create(BucketInterface::TYPE_GEO_DISTANCE, $params);

        $this->doStructureTest($bucket);
        $this->doContentTest($bucket, $params);
    }

    public function createDataProvider(): iterable
    {
        $queryFactory = static::getContainer()->get(QueryFactory::class);

        yield [[
            'name' => 'test_bucket_name',
            'field' => 'test_field',
            'origin' => '12 12',
            'ranges' => [
                ['to' => 200],
            ],
        ]];

        yield [[
            'name' => 'test_bucket_name',
            'field' => 'test_field',
            'nestedPath' => 'test',
            'origin' => '12 12',
            'ranges' => [
                ['to' => 200],
            ],
        ]];

        yield [[
            'name' => 'test_bucket_name',
            'field' => 'test_field',
            'nestedPath' => 'test',
            'minDocCount' => 10,
            'origin' => '12 12',
            'ranges' => [
                ['to' => 200],
            ],
        ]];

        yield [[
            'name' => 'test_bucket_name',
            'field' => 'test_field',
            'origin' => '12 12',
            'ranges' => [
                ['to' => 200],
            ],
            'filter' => $queryFactory->create(
                QueryInterface::TYPE_TERM,
                ['value' => 'red', 'field' => 'color']
            ),
            'nestedFilter' => $queryFactory->create(
                QueryInterface::TYPE_EXISTS,
                ['field' => 'sku']
            ),
            'childAggregations' => [
            ],
        ]];
    }

    protected function doStructureTest(mixed $bucket): void
    {
        parent::doStructureTest($bucket);
        $this->assertInstanceOf(GeoDistance::class, $bucket);
        $this->assertEquals(BucketInterface::TYPE_GEO_DISTANCE, $bucket->getType());

        $this->assertIsString($bucket->getOrigin());
        $this->assertIsArray($bucket->getRanges());
    }

    protected function doContentTest(mixed $bucket, array $params): void
    {
        parent::doContentTest($bucket, $params);

        /** @var GeoDistance $bucket */
        $this->assertEquals($params['origin'], $bucket->getOrigin());
        $this->assertEquals($params['ranges'], $bucket->getRanges());
    }
}
