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
use Gally\Metadata\Entity\Attribute\Type\BooleanAttribute;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BooleanAttributeTest extends KernelTestCase
{
    public function testInstantiateFailure(): void
    {
        $this->expectException(\ArgumentCountError::class);
        $booleanAttribute = $this->getMockBuilder(BooleanAttribute::class)
            ->getMock();
    }

    /**
     * @dataProvider sanitizationDataProvider
     *
     * @param string $attributeCode      Attribute code
     * @param mixed  $value              Attribute hydration value
     * @param mixed  $basicExpectedValue Expected value with basic sanitization
     * @param mixed  $extraExpectedValue Expected value with advanced sanitization
     */
    public function testSanitization(string $attributeCode, mixed $value, mixed $basicExpectedValue, mixed $extraExpectedValue): void
    {
        $reflector = new \ReflectionClass(BooleanAttribute::class);
        $attributeCodeProperty = $reflector->getProperty('attributeCode');
        $valueProperty = $reflector->getProperty('value');

        $attributeFactory = static::getContainer()->get(AttributeFactory::class);
        $booleanAttribute = $attributeFactory->create(
            BooleanAttribute::ATTRIBUTE_TYPE,
            ['attributeCode' => $attributeCode, 'value' => $value]
        );
        $this->assertEquals($attributeCode, $attributeCodeProperty->getValue($booleanAttribute));
        $this->assertEquals($booleanAttribute->getValue(), $valueProperty->getValue($booleanAttribute));
        $this->assertIsNotArray($valueProperty->getValue($booleanAttribute));
        $this->assertIsNotArray($booleanAttribute->getValue());

        if ($basicExpectedValue === $extraExpectedValue) {
            $this->assertEquals($basicExpectedValue, $booleanAttribute->getValue());
        } elseif (!$reflector->hasProperty('extraSanitization')) {
            $this->assertEquals($basicExpectedValue, $booleanAttribute->getValue());
        } else {
            $expectedValue = $basicExpectedValue;
            if ($reflector->getProperty('extraSanitization')->getValue($booleanAttribute)) {
                $expectedValue = $extraExpectedValue;
                $this->assertIsBool($booleanAttribute->getValue());
            }
            $this->assertEquals($expectedValue, $booleanAttribute->getValue());
        }
    }

    public function sanitizationDataProvider(): array
    {
        return [
            // null is invariant.
            ['myBoolean', null, null, null],
            // non-scalar is transformed as scalar + null invariant.
            ['myBoolean', [null], null, null],

            ['myBoolean', true, true, true],
            ['myBoolean', false, false, false],
            ['myBoolean', [true, false], true, true],
            ['myBoolean', [false, true], false, false],

            // deep non-scalar support.
            ['myBoolean', ['a' => [true, false], 'b' => [false, true]], true, true],
            ['myBoolean', ['a' => [false, true], 'b' => [true, false]], false, false],

            // extra sanitization variations.
            ['myBoolean', 'true', 'true', true],
            ['myBoolean', ['true'], 'true', true],
            ['myBoolean', 1, true, true],
            ['myBoolean', 0, false, false],
            ['myBoolean', [1], true, true],
            ['myBoolean', [0], false, false],
            ['myBoolean', '1', true, true],
            ['myBoolean', '0', '0', false],
            ['myBoolean', ' 1 ', true, true],
            ['myBoolean', ' 0 ', ' 0 ', true],
            ['myBoolean', 'myValue', 'myValue', true],
            ['myBoolean', ['myValue'], 'myValue', true],
        ];
    }
}
