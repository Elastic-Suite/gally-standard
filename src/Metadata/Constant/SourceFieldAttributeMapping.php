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

namespace Gally\Metadata\Constant;

use Gally\Metadata\Entity\SourceField\Type as SourceFieldType;

class SourceFieldAttributeMapping
{
    /**
     * @Todo: Move TYPES to config.
     */
    public const TYPES = [
        SourceFieldType::TYPE_TEXT => \Gally\Metadata\Entity\Attribute\Type\TextAttribute::class,
        SourceFieldType::TYPE_KEYWORD => \Gally\Metadata\Entity\Attribute\Type\TextAttribute::class,
        SourceFieldType::TYPE_SELECT => \Gally\Metadata\Entity\Attribute\Type\SelectAttribute::class,
        SourceFieldType::TYPE_INT => \Gally\Metadata\Entity\Attribute\Type\IntAttribute::class,
        SourceFieldType::TYPE_BOOLEAN => \Gally\Metadata\Entity\Attribute\Type\BooleanAttribute::class,
        SourceFieldType::TYPE_FLOAT => \Gally\Metadata\Entity\Attribute\Type\FloatAttribute::class,
        SourceFieldType::TYPE_PRICE => \Gally\Metadata\Entity\Attribute\Type\PriceAttribute::class,
        SourceFieldType::TYPE_STOCK => \Gally\Metadata\Entity\Attribute\Type\StockAttribute::class,
        SourceFieldType::TYPE_CATEGORY => \Gally\Metadata\Entity\Attribute\Type\CategoryAttribute::class,
        SourceFieldType::TYPE_REFERENCE => \Gally\Metadata\Entity\Attribute\Type\TextAttribute::class,
        SourceFieldType::TYPE_IMAGE => \Gally\Metadata\Entity\Attribute\Type\TextAttribute::class,
        SourceFieldType::TYPE_OBJECT => \Gally\Metadata\Entity\Attribute\Type\TextAttribute::class,
        SourceFieldType::TYPE_DATE => \Gally\Metadata\Entity\Attribute\Type\TextAttribute::class,
        SourceFieldType::TYPE_LOCATION => \Gally\Metadata\Entity\Attribute\Type\TextAttribute::class,
    ];
}
