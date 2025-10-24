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

namespace Gally\Search\Tests\Unit\Elasticsearch\Builder\Request\Query\Filter;

use Gally\Configuration\Service\ConfigurationManager;
use Gally\DependencyInjection\GenericFactory;
use Gally\Index\Entity\Index\Mapping;
use Gally\Index\Entity\Index\Mapping\Field;
use Gally\Index\Entity\Index\Mapping\FieldInterface;
use Gally\Metadata\Entity\Metadata;
use Gally\Metadata\Entity\SourceField;
use Gally\Search\Elasticsearch\Builder\Request\Query\Filter\FilterQueryBuilder;
use Gally\Search\Elasticsearch\Request\ContainerConfigurationInterface;
use Gally\Search\Elasticsearch\Request\QueryFactory;
use Gally\Search\Elasticsearch\Request\QueryInterface;
use Gally\Search\Entity\Facet\Configuration;
use Gally\Search\Repository\Facet\ConfigurationRepository;
use Gally\Search\Service\ReverseSourceFieldProvider;
use Gally\Search\Service\SearchContext;
use Gally\Test\AbstractTestCase;

class FilterQueryBuilderTest extends AbstractTestCase
{
    private array $mockedQueryTypes = [
        QueryInterface::TYPE_TERMS,
        QueryInterface::TYPE_TERM,
        QueryInterface::TYPE_RANGE,
        QueryInterface::TYPE_MATCH,
        QueryInterface::TYPE_BOOL,
        QueryInterface::TYPE_NESTED,
        QueryInterface::TYPE_NOT,
    ];

    private static array $fields = [];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        static::loadFixture([
            __DIR__ . '/../../../../../../fixtures/facet_configuration.yaml',
            __DIR__ . '/../../../../../../fixtures/categories.yaml',
            __DIR__ . '/../../../../../../fixtures/source_field.yaml',
            __DIR__ . '/../../../../../../fixtures/metadata.yaml',
        ]);

        self::$fields = [
            new Field('id', FieldInterface::FIELD_TYPE_INTEGER),
            new Field('simpleTextField', FieldInterface::FIELD_TYPE_KEYWORD),
            new Field('analyzedField', FieldInterface::FIELD_TYPE_TEXT, null, ['is_searchable' => true, 'is_filterable' => false]),
            new Field('nested.child', FieldInterface::FIELD_TYPE_KEYWORD, 'nested'),
            new Field(
                'color.value',
                FieldInterface::FIELD_TYPE_KEYWORD,
                null,
                ['is_filterable' => true, 'filter_logical_operator' => Configuration::FILTER_LOGICAL_OPERATOR_AND]
            ),
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
        }
    }

    /**
     * Test negative query conditions.
     */
    public function testNegativeQueryFilters(): void
    {
        $negativeConditions = ['neq', 'sneq', 'nin'];
        foreach ($negativeConditions as $condition) {
            $query = $this->buildQuery(['id' => [$condition => 1]]);
            $this->assertInstanceOf(QueryInterface::class, $query);
            $this->assertEquals(QueryInterface::TYPE_NOT, $query->getType());
        }
    }

    /**
     * Test query condition on field configured to bind multiple values with a logical AND
     * (instead of the default logical OR).
     *
     * @dataProvider multipleValuesLogicalAndQueryFilterDataProvider
     */
    public function testMultipleValuesLogicalAndQueryFilter(string|array $innerConditionValue, string $expectedQueryType): void
    {
        $query = $this->buildQuery(['color.value' => $innerConditionValue]);
        $this->assertInstanceOf(QueryInterface::class, $query);
        $this->assertEquals($expectedQueryType, $query->getType());
    }

    /**
     * Data provider for test method testMultipleValuesLogicalAndQueryFilter.
     */
    public function multipleValuesLogicalAndQueryFilterDataProvider(): array
    {
        return [
            [['filterValue1', 'filterValue2'], QueryInterface::TYPE_BOOL],
            ['filterValue1,filterValue2', QueryInterface::TYPE_TERM],
            [['in' => 'filterValue1,filterValue2'], QueryInterface::TYPE_BOOL],
            [['filterValue1'], QueryInterface::TYPE_TERM],
            [['in' => 'filterValue1'], QueryInterface::TYPE_TERM],
            ['filterValue1', QueryInterface::TYPE_TERM],
        ];
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
     * Test conditions on nested fields with invalid nested path.
     */
    public function testInvalidNestedPathFieldFilter(): void
    {
        $this->expectExceptionMessage('Can not filter nested field nested.child with nested path otherNestedPath');
        $this->expectException(\LogicException::class);
        $this->buildQuery(['nested.child' => 'filterValue'], 'otherNestedPath');
    }

    /**
     * Test applying a nested path on a non-nested field.
     */
    public function testInvalidNestedOnNonNestedFieldFilter(): void
    {
        $this->expectExceptionMessage('Can not filter non nested field simpleTextField in nested context (otherNestedPath)');
        $this->expectException(\LogicException::class);
        $this->buildQuery(['simpleTextField' => 'filterValue'], 'otherNestedPath');
    }

    /**
     * Test using an not supported exception throws an exception.
     */
    public function testUnsupportedCondition(): void
    {
        $this->expectExceptionMessage('Condition regexp is not supported.');
        $this->expectException(\LogicException::class);
        $this->buildQuery(['simpleTextField' => ['regexp' => 'filterValue']]);
    }

    /**
     * Generate a query from conditions using mocked objects.
     *
     * @param array   $conditions conditions
     * @param ?string $nestedPath nested path or null
     */
    private function buildQuery(array $conditions, ?string $nestedPath = null): QueryInterface
    {
        $builder = new FilterQueryBuilder(
            $this->getQueryFactory($this->mockedQueryTypes),
            static::getContainer()->get(SearchContext::class),
            static::getContainer()->get(ConfigurationManager::class),
            static::getContainer()->get(ReverseSourceFieldProvider::class),
            static::getContainer()->get(ConfigurationRepository::class),
        );
        $config = $this->getContainerConfigMock(self::$fields);

        return $builder->create($config, $conditions, $nestedPath);
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
    private function getContainerConfigMock(array $fields): ContainerConfigurationInterface
    {
        $config = $this->getMockBuilder(ContainerConfigurationInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata = $this->getMockBuilder(Metadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $colorSourceField = new SourceField();
        $colorSourceField->setId(17);
        $colorSourceField->setCode('color');
        $colorSourceField->setType(SourceField\Type::TYPE_SELECT);

        $metadata->method('getEntity')->willReturn('product_document');
        $metadata->method('getSourceFieldByCodes')->willReturnMap(
            [
                [['simpleTextField', 'simpleTextField'], []],
                [['id', 'id'], []],
                [['analyzedField', 'analyzedField'], []],
                [['nested.child', 'nested'], []],
                [['color.value', 'color'], [$colorSourceField]],
            ]
        );

        $mapping = new Mapping($fields);

        $config->method('getMapping')->willReturn($mapping);
        $config->method('getMetadata')->willReturn($metadata);

        return $config;
    }
}
