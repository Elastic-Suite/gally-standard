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

use Gally\Search\Elasticsearch\Request\Aggregation\Bucket\SignificantTerms;
use Gally\Search\Elasticsearch\Request\BucketInterface;
use Gally\Search\Elasticsearch\Request\QueryFactory;
use Gally\Search\Elasticsearch\Request\QueryInterface;

class SignificantTermsTest extends AbstractBucketTestCase
{
    public function testFailedCreate(): void
    {
        $this->expectException(\ArgumentCountError::class);
        self::$aggregationFactory->create(BucketInterface::TYPE_SIGNIFICANT_TERMS);
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testDefaultCreate(array $params): void
    {
        /** @var SignificantTerms $bucket */
        $bucket = self::$aggregationFactory->create(BucketInterface::TYPE_SIGNIFICANT_TERMS, $params);

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
            'size' => 10,
        ]];

        yield [[
            'name' => 'test_bucket_name',
            'field' => 'created_at',
            'nestedPath' => 'category',
            'size' => 10,
            'minDocCount' => 10,
        ]];

        yield [[
            'name' => 'test_bucket_name',
            'field' => 'created_at',
            'nestedPath' => 'category',
            'size' => 10,
            'minDocCount' => 10,
            'algorithm' => SignificantTerms::ALGORITHM_PERCENTAGE,
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
                //                TODO : does this need to be build before like queries ?
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
        $this->assertInstanceOf(SignificantTerms::class, $bucket);
        $this->assertEquals(BucketInterface::TYPE_SIGNIFICANT_TERMS, $bucket->getType());

        $this->assertIsInt($bucket->getSize());
        $this->assertIsInt($bucket->getMinDocCount());
        $this->assertIsString($bucket->getAlgorithm());
    }

    protected function doContentTest(mixed $bucket, array $params): void
    {
        parent::doContentTest($bucket, $params);

        /** @var SignificantTerms $bucket */
        $this->assertEquals($params['size'] ?? BucketInterface::MAX_BUCKET_SIZE, $bucket->getSize());
        $this->assertEquals($params['minDocCount'] ?? 5, $bucket->getMinDocCount());
        $this->assertEquals($params['algorithm'] ?? SignificantTerms::ALGORITHM_GND, $bucket->getAlgorithm());
    }
}
