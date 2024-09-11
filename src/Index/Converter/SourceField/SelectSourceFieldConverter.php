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

namespace Gally\Index\Converter\SourceField;

use Gally\Index\Entity\Index\Mapping;
use Gally\Metadata\Entity\SourceField;

class SelectSourceFieldConverter implements SourceFieldConverterInterface
{
    public function supports(SourceField $sourceField): bool
    {
        return SourceField\Type::TYPE_SELECT === $sourceField->getType();
    }

    public function getFields(SourceField $sourceField): array
    {
        $fields = [];

        $fieldCode = \sprintf('%s.value', $sourceField->getCode());
        $path = $sourceField->getCode();
        /*
         * Do NOT support nested select fields for the moment, ie super.brand
         * to generate super.brand.value and super.brand.label
         * ---
         * $path = $sourceField->getNestedPath();
         *
         * $fieldType = Mapping\FieldInterface::FIELD_TYPE_INT;
         */
        $fieldType = Mapping\FieldInterface::FIELD_TYPE_KEYWORD;

        $fields[$fieldCode] = new Mapping\Field($fieldCode, $fieldType, $path);

        $fieldCode = \sprintf('%s.label', $sourceField->getCode());
        $fieldConfig = [
            'is_searchable' => $sourceField->getIsSearchable(),
            'is_used_in_spellcheck' => $sourceField->getIsSpellchecked(),
            'is_filterable' => $sourceField->getIsFilterable() || $sourceField->getIsUsedForRules() || $sourceField->getIsUsedInAutocomplete(),
            'search_weight' => $sourceField->getWeight(),
            'is_used_for_sort_by' => $sourceField->getIsSortable(),
        ];

        $fields[$fieldCode] = new Mapping\Field($fieldCode, Mapping\FieldInterface::FIELD_TYPE_TEXT, $path, $fieldConfig);

        return $fields;
    }
}
