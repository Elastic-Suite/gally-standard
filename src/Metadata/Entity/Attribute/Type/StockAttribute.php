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
use Gally\Metadata\Entity\Attribute\StructuredAttributeInterface;

class StockAttribute extends AbstractStructuredAttribute implements AttributeInterface, StructuredAttributeInterface
{
    public const ATTRIBUTE_TYPE = 'stock';

    public static function getFields(): array
    {
        // Possible additional fields in the future.
        return [
            'status' => ['class_type' => BooleanAttribute::class],
            'qty' => ['class_type' => FloatAttribute::class],
        ];
    }

    public static function isList(): bool
    {
        return false;
    }
}
