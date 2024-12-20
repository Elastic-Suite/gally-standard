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

use Gally\Metadata\GraphQl\Type\Definition\Filter\SelectTypeDefaultFilterInputType;
use Gally\Search\Constant\FilterOperator;
use Gally\Search\Elasticsearch\Builder\Request\Query\Filter\FilterQueryBuilder;
use Gally\Search\Elasticsearch\Request\ContainerConfigurationInterface;
use Gally\Search\Elasticsearch\Request\QueryFactory;
use GraphQL\Type\Definition\Type;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SelectTypeDefaultFilterInputTypeTest extends KernelTestCase
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
        $reflector = new \ReflectionClass(SelectTypeDefaultFilterInputType::class);

        $nestingSeparatorProperty = $reflector->getProperty('nestingSeparator');
        $nameProperty = $reflector->getProperty('name');
        $configProperty = $reflector->getProperty('config');

        $selectTypeDefaultFilterInputType = new SelectTypeDefaultFilterInputType(
            self::$filterQueryBuilder,
            self::$queryFactory,
            '##'
        );

        $this->assertEquals(
            [
                'fields' => [
                    FilterOperator::EQ => Type::string(),
                    FilterOperator::IN => Type::listOf(Type::string()),
                    FilterOperator::EXIST => Type::boolean(),
                ],
            ],
            $selectTypeDefaultFilterInputType->getConfig()
        );

        $this->assertEquals('##', $nestingSeparatorProperty->getValue($selectTypeDefaultFilterInputType));

        $this->assertEquals(
            SelectTypeDefaultFilterInputType::SPECIFIC_NAME,
            $nameProperty->getValue($selectTypeDefaultFilterInputType)
        );
        $this->assertEquals(
            SelectTypeDefaultFilterInputType::SPECIFIC_NAME,
            $selectTypeDefaultFilterInputType->getName()
        );
        $this->assertEquals(
            $selectTypeDefaultFilterInputType->getConfig(),
            $configProperty->getValue($selectTypeDefaultFilterInputType)
        );
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
        $selectTypeDefaultFilterInputType = new SelectTypeDefaultFilterInputType(
            self::$filterQueryBuilder,
            self::$queryFactory,
            '__'
        );

        $errors = $selectTypeDefaultFilterInputType->validate(
            $fieldName,
            $inputData,
            $this->getMockBuilder(ContainerConfigurationInterface::class)->getMock(),
        );
        $this->assertEquals($expectedErrors, $errors);
    }

    public function validateDataProvider(): array
    {
        return [
            ['fashion_color__value', ['eq' => '24'], []],
            ['fashion_color__value', ['in' => ['25', '37']], []],
            ['fashion_color__value', ['exist' => true], []],
            [
                'fashion_color__value',
                ['eq' => '24', 'in' => ['25', '37']],
                ["Filter argument fashion_color__value: Only 'eq', 'in' or 'exist' should be filled."],
            ],
            [
                'fashion_color__value',
                [],
                ["Filter argument fashion_color__value: At least 'eq', 'in' or 'exist' should be filled."],
            ],
        ];
    }
}
