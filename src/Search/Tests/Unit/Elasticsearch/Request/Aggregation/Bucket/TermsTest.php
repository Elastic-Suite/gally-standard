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

use Gally\Search\Elasticsearch\Request\Aggregation\Bucket\Terms;
use Gally\Search\Elasticsearch\Request\BucketInterface;
use Gally\Search\Elasticsearch\Request\MetricInterface;
use Gally\Search\Elasticsearch\Request\QueryFactory;
use Gally\Search\Elasticsearch\Request\QueryInterface;
use PHPUnit\Framework\Constraint\IsType;
use PHPUnit\Framework\Constraint\LogicalOr;

class TermsTest extends AbstractBucketTestCase
{
    public function testFailedCreate(): void
    {
        $this->expectException(\ArgumentCountError::class);
        self::$aggregationFactory->create(BucketInterface::TYPE_TERMS);
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testDefaultCreate(array $params): void
    {
        /** @var Terms $bucket */
        $bucket = self::$aggregationFactory->create(BucketInterface::TYPE_TERMS, $params);

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
            'sortOrder' => BucketInterface::SORT_ORDER_TERM,
        ]];

        yield [[
            'name' => 'test_bucket_name',
            'field' => 'created_at',
            'nestedPath' => 'category',
            'size' => 10,
            'minDocCount' => 10,
            'sortOrder' => BucketInterface::SORT_ORDER_TERM,
            'include' => [
                'red',
                'blue',
            ],
        ]];

        yield [[
            'name' => 'test_bucket_name',
            'field' => 'created_at',
            'nestedPath' => 'category',
            'size' => 10,
            'minDocCount' => 10,
            'sortOrder' => BucketInterface::SORT_ORDER_TERM,
            'exclude' => [
                'red',
                'blue',
            ],
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
                    'type' => MetricInterface::TYPE_SUM,
                    'field' => 'test_field',
                ],
            ],
        ]];
    }

    protected function doStructureTest(mixed $bucket): void
    {
        parent::doStructureTest($bucket);
        $this->assertInstanceOf(Terms::class, $bucket);
        $this->assertEquals(BucketInterface::TYPE_TERMS, $bucket->getType());

        $this->assertIsInt($bucket->getSize());
        $this->assertIsString($bucket->getSortOrder());
        $this->assertIsArray($bucket->getInclude());
        $this->assertIsArray($bucket->getExclude());
        $this->assertThat($bucket->getMinDocCount(), LogicalOr::fromConstraints(new IsType('null'), new IsType('int')));
    }

    protected function doContentTest(mixed $bucket, array $params): void
    {
        parent::doContentTest($bucket, $params);

        /** @var Terms $bucket */
        $this->assertEquals($params['size'] ?? BucketInterface::MAX_BUCKET_SIZE, $bucket->getSize());
        $this->assertEquals($params['sortOrder'] ?? BucketInterface::SORT_ORDER_COUNT, $bucket->getSortOrder());
        $this->assertEquals($params['include'] ?? [], $bucket->getInclude());
        $this->assertEquals($params['exclude'] ?? [], $bucket->getExclude());
        $this->assertEquals($params['minDocCount'] ?? null, $bucket->getMinDocCount());
    }
}
