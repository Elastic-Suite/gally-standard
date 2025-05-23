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

use Gally\Search\Elasticsearch\Request\Aggregation\Bucket\Histogram;
use Gally\Search\Elasticsearch\Request\BucketInterface;
use Gally\Search\Elasticsearch\Request\QueryFactory;
use Gally\Search\Elasticsearch\Request\QueryInterface;
use PHPUnit\Framework\Constraint\IsType;
use PHPUnit\Framework\Constraint\LogicalOr;

class HistogramTest extends AbstractBucketTestCase
{
    public function testFailedCreate(): void
    {
        $this->expectException(\ArgumentCountError::class);
        self::$aggregationFactory->create(BucketInterface::TYPE_HISTOGRAM);
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testDefaultCreate(array $params): void
    {
        /** @var Histogram $bucket */
        $bucket = self::$aggregationFactory->create(BucketInterface::TYPE_HISTOGRAM, $params);

        $this->doStructureTest($bucket);
        $this->doContentTest($bucket, $params);
    }

    public function createDataProvider(): iterable
    {
        $queryFactory = static::getContainer()->get(QueryFactory::class);
        yield [[
            'name' => 'test_bucket_name',
            'field' => 'test_field',
        ]];

        yield [[
            'name' => 'test_bucket_name',
            'field' => 'test_field',
            'interval' => 0,
        ]];

        yield [[
            'name' => 'test_bucket_name',
            'field' => 'created_at',
            'nestedPath' => 'category',
            'interval' => '2y',
            'minDocCount' => 10,
        ]];

        yield [[
            'name' => 'test_bucket_name',
            'field' => 'ca',
            'interval' => '2y',
            'filter' => $queryFactory->create(
                QueryInterface::TYPE_TERM,
                ['value' => 'red', 'field' => 'color']
            ),
            'nestedFilter' => $queryFactory->create(
                QueryInterface::TYPE_EXISTS,
                ['field' => 'sku']
            ),
            'childAggregations' => [
                [
                    'type' => 'sumMetric',
                    'field' => 'test_field',
                ],
            ],
        ]];
    }

    protected function doStructureTest(mixed $bucket): void
    {
        parent::doStructureTest($bucket);
        $this->assertInstanceOf(Histogram::class, $bucket);
        $this->assertEquals(BucketInterface::TYPE_HISTOGRAM, $bucket->getType());

        $this->assertThat($bucket->getInterval(), LogicalOr::fromConstraints(new IsType('string'), new IsType('int')));
        $this->assertIsInt($bucket->getMinDocCount());
    }

    protected function doContentTest(mixed $bucket, array $params): void
    {
        parent::doContentTest($bucket, $params);

        /** @var Histogram $bucket */
        $this->assertEquals($params['interval'] ?? 1, $bucket->getInterval());
        $this->assertEquals($params['minDocCount'] ?? 0, $bucket->getMinDocCount());
    }
}
