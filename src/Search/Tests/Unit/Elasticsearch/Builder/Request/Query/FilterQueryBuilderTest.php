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

namespace Gally\Search\Tests\Unit\Elasticsearch\Builder\Request\Query;

use Gally\Configuration\Service\ConfigurationManager;
use Gally\DependencyInjection\GenericFactory;
use Gally\Index\Entity\Index\Mapping;
use Gally\Index\Entity\Index\Mapping\Field;
use Gally\Index\Entity\Index\Mapping\FieldInterface;
use Gally\Search\Elasticsearch\Builder\Request\Query\Filter\FilterQueryBuilder;
use Gally\Search\Elasticsearch\Request\ContainerConfigurationInterface;
use Gally\Search\Elasticsearch\Request\QueryFactory;
use Gally\Search\Elasticsearch\Request\QueryInterface;
use Gally\Search\Service\SearchContext;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Filter query builder test case.
 */
class FilterQueryBuilderTest extends KernelTestCase
{
    private array $mockedQueryTypes = [
        QueryInterface::TYPE_TERMS,
        QueryInterface::TYPE_RANGE,
        QueryInterface::TYPE_DATE_RANGE,
        QueryInterface::TYPE_GEO_DISTANCE,
        QueryInterface::TYPE_MATCH,
        QueryInterface::TYPE_BOOL,
        QueryInterface::TYPE_NESTED,
        QueryInterface::TYPE_NOT,
    ];

    private static array $fields;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$fields = [
            new Field('id', 'integer'),
            new Field('simpleTextField', FieldInterface::FIELD_TYPE_KEYWORD),
            new Field('analyzedField', FieldInterface::FIELD_TYPE_TEXT, null, ['is_searchable' => true, 'is_filterable' => false]),
            new Field('nested.child', FieldInterface::FIELD_TYPE_KEYWORD, 'nested'),
            new Field('dateField', FieldInterface::FIELD_TYPE_DATE),
            new Field('locationField', FieldInterface::FIELD_TYPE_GEOPOINT),
        ];
    }

    /**
     * Test simple eq filter on the id field.
     */
    public function testSingleQueryFilter(): void
    {
        $query = $this->buildQuery(['simpleTextField' => 'filterValue']);
        $this->assertInstanceOf(QueryInterface::class, $query);
        $this->assertEquals(QueryInterface::TYPE_TERMS, $query->getType());

        $query = $this->buildQuery(['simpleTextField' => ['filterValue1', 'filterValue2']]);
        $this->assertInstanceOf(QueryInterface::class, $query);
        $this->assertEquals(QueryInterface::TYPE_TERMS, $query->getType());
    }

    /**
     * Test negative filter condition.
     */
    public function testNegativeFilterCondition(): void
    {
        $query = $this->buildQuery(['simpleTextField' => ['neq' => 'filterValue']]);
        $this->assertInstanceOf(QueryInterface::class, $query);
        $this->assertEquals(QueryInterface::TYPE_NOT, $query->getType());
    }

    /**
     * Test multiple fields query filter.
     */
    public function testMultipleQueryFilter(): void
    {
        $query = $this->buildQuery(['simpleTextField' => 'filterValue', 'id' => 1]);

        $this->assertInstanceOf(QueryInterface::class, $query);
        $this->assertEquals(QueryInterface::TYPE_BOOL, $query->getType());
    }

    /**
     * Test range query conditions.
     */
    public function testRangeQueryFilters(): void
    {
        $rangeConditions = ['lteq', 'lte', 'lt', 'gteq', 'gte', 'moreq', 'gt'];
        foreach ($rangeConditions as $condition) {
            $query = $this->buildQuery(['id' => [$condition => 1]]);
            $this->assertInstanceOf(QueryInterface::class, $query);
            $this->assertEquals(QueryInterface::TYPE_RANGE, $query->getType());

            $query = $this->buildQuery(['dateField' => [$condition => 1]]);
            $this->assertInstanceOf(QueryInterface::class, $query);
            $this->assertEquals(QueryInterface::TYPE_DATE_RANGE, $query->getType());
        }
    }

    /**
     * Test geo distance query conditions.
     */
    public function testGeoDistanceQueryFilters(): void
    {
        $query = $this->buildQuery(['locationField' => ['lte' => 1]]);
        $this->assertInstanceOf(QueryInterface::class, $query);
        $this->assertEquals(QueryInterface::TYPE_GEO_DISTANCE, $query->getType());
    }

    /**
     * Test fulltext query conditions.
     */
    public function testFulltextQueryFilter(): void
    {
        $query = $this->buildQuery(['simpleTextField' => ['like' => 'fulltext']]);
        $this->assertInstanceOf(QueryInterface::class, $query);
        $this->assertEquals(QueryInterface::TYPE_TERMS, $query->getType());

        $query = $this->buildQuery(['analyzedField' => ['like' => 'fulltext']]);
        $this->assertInstanceOf(QueryInterface::class, $query);
        $this->assertEquals(QueryInterface::TYPE_MATCH, $query->getType());
    }

    /**
     * Test using a raw query as condition.
     */
    public function testRawQueryFilter(): void
    {
        $query = $this->getMockBuilder(QueryInterface::class)->getMock();
        $queryFilter = $this->buildQuery(['simpleTextField' => $query]);

        $this->assertInstanceOf(QueryInterface::class, $queryFilter);
    }

    /**
     * Test conditions on nested fields.
     */
    public function testNestedFieldFilter(): void
    {
        $query = $this->buildQuery(['nested.child' => 'filterValue']);

        $this->assertInstanceOf(QueryInterface::class, $query);
        $this->assertEquals(QueryInterface::TYPE_NESTED, $query->getType());
    }

    /**
     * Test conditions on nested fields.
     */
    public function testNestedErrorFieldFilter(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Can not filter nested field nested.child with nested path invalidCustomPath');
        $this->buildQuery(['nested.child' => 'filterValue'], 'invalidCustomPath');
    }

    /**
     * Test conditions on nested fields.
     */
    public function testNestedError2FieldFilter(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Can not filter non nested field simpleTextField in nested context (nested)');
        $this->buildQuery(['simpleTextField' => 'filterValue'], 'nested');
    }

    /**
     * Test using a not supported exception throws an exception.
     */
    public function testUnsupportedCondition(): void
    {
        $this->expectExceptionMessage('Condition regexp is not supported.');
        $this->expectException(\LogicException::class);
        $this->buildQuery(['simpleTextField' => ['regexp' => 'filterValue']]);
    }

    /**
     * Generate a query from conditions using mocked objects.
     */
    private function buildQuery(array $conditions, ?string $currentPath = null): QueryInterface
    {
        $builder = new FilterQueryBuilder(
            $this->getQueryFactory($this->mockedQueryTypes),
            static::getContainer()->get(SearchContext::class),
            static::getContainer()->get(ConfigurationManager::class),
        );
        $config = $this->getContainerConfigMock(self::$fields);

        return $builder->create($config, $conditions, $currentPath);
    }

    /**
     * Mock the query factory used by the builder.
     *
     * @param string[] $queryTypes mocked query types
     */
    private function getQueryFactory(array $queryTypes): QueryFactory
    {
        $factories = [];

        foreach ($queryTypes as $currentType) {
            $queryMock = $this->getMockBuilder(QueryInterface::class)->getMock();
            $queryMock->method('getType')->willReturn($currentType);

            $factory = $this->getMockBuilder(GenericFactory::class)->getMock();
            $factory->method('create')->willReturn($queryMock);

            $factories[$currentType] = $factory;
        }

        return new QueryFactory($factories);
    }

    /**
     * Mock the configuration used by the query builder.
     *
     * @param FieldInterface[] $fields mapping fields
     */
    private function getContainerConfigMock(array $fields): MockObject|ContainerConfigurationInterface
    {
        $config = $this->getMockBuilder(ContainerConfigurationInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mapping = new Mapping($fields);
        $config->method('getMapping')->willReturn($mapping);

        return $config;
    }
}
