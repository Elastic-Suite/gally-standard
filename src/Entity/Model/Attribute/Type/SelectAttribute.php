<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @package   Gally
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Gally\Entity\Model\Attribute\Type;

use Gally\Entity\Model\Attribute\AttributeInterface;
use Gally\Entity\Model\Attribute\StructuredAttributeInterface;

/**
 * Used for normalization/de-normalization and graphql schema stitching of select boolean source fields.
 */
class SelectAttribute extends AbstractStructuredAttribute implements AttributeInterface, StructuredAttributeInterface
{
    public const ATTRIBUTE_TYPE = 'select';

    /**
     * {@inheritDoc}
     */
    public static function getFields(): array
    {
        return [
            'label' => ['class_type' => TextAttribute::class],
            'value' => ['class_type' => TextAttribute::class],
        ];
    }
}
