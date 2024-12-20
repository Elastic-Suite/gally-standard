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

namespace Gally\Product\Tests\Unit\GraphQl\Type\Definition\Filter;

use Gally\Metadata\GraphQl\Type\Definition\Filter\StockTypeDefaultFilterInputType;
use Gally\Search\Constant\FilterOperator;
use Gally\Search\Elasticsearch\Builder\Request\Query\Filter\FilterQueryBuilder;
use Gally\Search\Elasticsearch\Request\ContainerConfigurationInterface;
use Gally\Search\Elasticsearch\Request\QueryFactory;
use GraphQL\Type\Definition\Type;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class StockTypeDefaultFilterInputTest extends KernelTestCase
{
    private static FilterQueryBuilder $filterQueryBuilder;

    private static QueryFactory $queryFactory;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$filterQueryBuilder = static::getContainer()->get(FilterQueryBuilder::class);
        self::$queryFactory = static::getContainer()->get(QueryFactory::class);
    }

    public function testInstantiate(): void
    {
        $reflector = new \ReflectionClass(StockTypeDefaultFilterInputType::class);

        $nestingSeparatorProperty = $reflector->getProperty('nestingSeparator');
        $nameProperty = $reflector->getProperty('name');
        $configProperty = $reflector->getProperty('config');

        $stockTypeDefaultFilterInputType = new StockTypeDefaultFilterInputType(
            self::$filterQueryBuilder,
            self::$queryFactory,
            '##'
        );

        $this->assertEquals(
            [
                'fields' => [
                    FilterOperator::EQ => Type::boolean(),
                    FilterOperator::EXIST => Type::boolean(),
                ],
            ],
            $stockTypeDefaultFilterInputType->getConfig()
        );

        $this->assertEquals('##', $nestingSeparatorProperty->getValue($stockTypeDefaultFilterInputType));

        $this->assertEquals(
            StockTypeDefaultFilterInputType::SPECIFIC_NAME,
            $nameProperty->getValue($stockTypeDefaultFilterInputType)
        );
        $this->assertEquals(
            StockTypeDefaultFilterInputType::SPECIFIC_NAME,
            $stockTypeDefaultFilterInputType->getName()
        );
        $this->assertEquals(
            $stockTypeDefaultFilterInputType->getConfig(),
            $configProperty->getValue($stockTypeDefaultFilterInputType)
        );
    }

    public function testFieldNames(): void
    {
        $stockTypeDefaultFilterInputType = new StockTypeDefaultFilterInputType(
            self::$filterQueryBuilder,
            self::$queryFactory,
            '##'
        );

        $this->assertEquals('my_stock.status', $stockTypeDefaultFilterInputType->getFilterFieldName('my_stock'));
        $this->assertEquals('my_stock##status', $stockTypeDefaultFilterInputType->getGraphQlFieldName('my_stock.status'));
    }

    /**
     * @dataProvider validateDataProvider
     *
     * @param string $fieldName      Field name
     * @param array  $inputData      Input data
     * @param array  $expectedErrors Array of expected error messages (empty if no errors expected)
     */
    public function testValidate(string $fieldName, array $inputData, array $expectedErrors): void
    {
        $stockTypeDefaultFilterInputType = new StockTypeDefaultFilterInputType(
            self::$filterQueryBuilder,
            self::$queryFactory,
            '__'
        );

        $errors = $stockTypeDefaultFilterInputType->validate(
            $fieldName,
            $inputData,
            $this->getMockBuilder(ContainerConfigurationInterface::class)->getMock(),
        );
        $this->assertEquals($expectedErrors, $errors);
    }

    public function validateDataProvider(): array
    {
        return [
            ['stock__status', ['eq' => true], []],
            ['stock__status', ['exist' => true], []],
            [
                'stock__status',
                ['eq' => true, 'exist' => true],
                ["Filter argument stock__status: Only 'eq' or 'exist' should be filled."],
            ],
            [
                'stock__status',
                [],
                ["Filter argument stock__status: At least 'eq' or 'exist' should be filled."],
            ],
        ];
    }
}
