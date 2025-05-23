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

class CategoryAttribute extends AbstractStructuredAttribute implements AttributeInterface, StructuredAttributeInterface
{
    public const ATTRIBUTE_TYPE = 'category';

    public static function getFields(): array
    {
        return [
            'id' => ['class_type' => TextAttribute::class],
            'uid' => ['class_type' => TextAttribute::class],
            'name' => ['class_type' => TextAttribute::class],
            'is_parent' => ['class_type' => BooleanAttribute::class],
            'is_virtual' => ['class_type' => BooleanAttribute::class],
            'is_blacklisted' => ['class_type' => BooleanAttribute::class],
            'position' => ['class_type' => IntAttribute::class],
        ];
    }
}
