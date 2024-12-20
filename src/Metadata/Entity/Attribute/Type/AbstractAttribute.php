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

namespace Gally\Metadata\Entity\Attribute\Type;

use Gally\Metadata\Entity\Attribute\AttributeInterface;

abstract class AbstractAttribute implements AttributeInterface
{
    protected string $attributeCode;

    protected mixed $value;

    public function __construct(string $attributeCode, mixed $value)
    {
        $this->attributeCode = $attributeCode;
        $this->value = $this->getSanitizedData($value);
    }

    public function getAttributeCode(): string
    {
        return $this->attributeCode;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Return the sanitized version of the attribute/field value according to the attribute/field type or structure.
     *
     * @param mixed $value Attribute/field value
     */
    abstract protected function getSanitizedData(mixed $value): mixed;
}
