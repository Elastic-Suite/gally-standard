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

use Gally\Metadata\Entity\Attribute\StructuredAttributeInterface;

abstract class AbstractStructuredAttribute extends AbstractAttribute implements StructuredAttributeInterface
{
    public static function getFields(): array
    {
        return [];
    }

    public static function isList(): bool
    {
        return true;
    }

    protected function getSanitizedData(mixed $value): mixed
    {
        if (\is_array($value) && !empty($value)) {
            $hasSingleEntry = \count(array_intersect(array_keys($value), array_keys(static::getFields()))) > 0;
            if ($hasSingleEntry && static::isList()) {
                $value = [$value];
            } elseif (!$hasSingleEntry && (false === static::isList())) {
                $value = current($value);
            }
        }

        return $value;
    }
}
