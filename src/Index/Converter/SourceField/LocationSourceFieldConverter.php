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

namespace Gally\Index\Converter\SourceField;

use Gally\Index\Model\Index\Mapping;
use Gally\Metadata\Model\SourceField;

class LocationSourceFieldConverter implements SourceFieldConverterInterface
{
    /**
     * {@inheritDoc}
     */
    public function supports(SourceField $sourceField): bool
    {
        return SourceField\Type::TYPE_LOCATION === $sourceField->getType();
    }

    /**
     * {@inheritDoc}
     */
    public function getFields(SourceField $sourceField): array
    {
        return [
            $sourceField->getCode() => new Mapping\Field(
                $sourceField->getCode(),
                Mapping\FieldInterface::FIELD_TYPE_GEOPOINT,
                $sourceField->getNestedPath(),
                [
                    'is_searchable' => false,
                    'is_used_in_spellcheck' => false,
                    'is_filterable' => $sourceField->getIsFilterable() || $sourceField->getIsUsedForRules() || $sourceField->getIsUsedInAutocomplete(),
                    'search_weight' => $sourceField->getWeight(),
                    'is_used_for_sort_by' => $sourceField->getIsSortable(),
                ]
            ),
        ];
    }
}
