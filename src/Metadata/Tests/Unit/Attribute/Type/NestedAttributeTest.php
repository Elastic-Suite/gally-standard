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

namespace Gally\Metadata\Tests\Unit\Attribute\Type;

use Gally\Metadata\Entity\Attribute\AttributeFactory;
use Gally\Metadata\Entity\Attribute\Type\NestedAttribute;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class NestedAttributeTest extends KernelTestCase
{
    public function testInstantiateFailure(): void
    {
        $this->expectException(\ArgumentCountError::class);
        $nestedAttribute = $this->getMockBuilder(NestedAttribute::class)
            ->getMock();
    }

    /**
     * @dataProvider structureCheckDataProvider
     *
     * @param string $attributeCode Attribute code
     * @param mixed  $value         Attribute hydration value
     * @param array  $fields        Attribute structure
     * @param mixed  $expectedValue Expected value with basic structure check
     */
    public function testStructureCheck(string $attributeCode, mixed $value, array $fields, mixed $expectedValue): void
    {
        $reflector = new \ReflectionClass(NestedAttribute::class);
        $attributeCodeProperty = $reflector->getProperty('attributeCode');
        $valueProperty = $reflector->getProperty('value');
        $fieldsProperty = $reflector->getProperty('fields');

        $attributeFactory = static::getContainer()->get(AttributeFactory::class);
        $nestedAttribute = $attributeFactory->create(
            NestedAttribute::ATTRIBUTE_TYPE,
            ['attributeCode' => $attributeCode, 'value' => $value, 'fields' => $fields]
        );
        $this->assertEquals($attributeCode, $attributeCodeProperty->getValue($nestedAttribute));
        $this->assertEquals($fields, $fieldsProperty->getValue($nestedAttribute));

        if (\is_scalar($value)) {
            $this->assertIsScalar($nestedAttribute->getValue());
        } elseif (null === $value) {
            $this->assertNull($nestedAttribute->getValue());
        }
        $this->assertEquals($expectedValue, $nestedAttribute->getValue());
        $this->assertEquals($expectedValue, $valueProperty->getValue($nestedAttribute));
    }

    public function structureCheckDataProvider(): array
    {
        return [
            ['myNested', null, [], null],
            ['myNested', true, [], true],
            ['myNested', false, [], false],
            ['myNested', 'myValue', [], 'myValue'],
            // Not sure if it is an acceptable behavior.
            ['myNested', ['myValue'], [], 'myValue'],
            ['myNested', [['myValue'], ['myOtherValue']], [], ['myValue']],
            ['myNested', [], [], []],
            ['myNested', [], ['fieldA', 'fieldB'], []],
            [
                'myNested',
                ['fieldA' => 'text', 'fieldB' => 0],
                ['fieldA', 'fieldB'],
                ['fieldA' => 'text', 'fieldB' => 0],
            ],
            // For the moment, nested attributes only output a single value.
            [
                'myNested',
                [['fieldA' => 'text', 'fieldB' => 0], ['fieldA' => 'otherText', 'fieldB' => -5]],
                ['fieldA', 'fieldB'],
                ['fieldA' => 'text', 'fieldB' => 0],
            ],
            // For the moment, no advanced nested structure checks.
            [
                'myNested',
                [['fieldA' => 'text', 'unknownField' => true], ['fieldA' => 'otherText', 'fieldB' => -5]],
                ['fieldA', 'fieldB'],
                ['fieldA' => 'text', 'unknownField' => true],
            ],
        ];
    }
}
