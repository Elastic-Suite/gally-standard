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

class TextSourceFieldConverter implements SourceFieldConverterInterface
{
    public function supports(SourceField $sourceField): bool
    {
        return SourceField\Type::TYPE_TEXT === $sourceField->getType();
    }

    public function getFields(SourceField $sourceField): array
    {
        $fields = [];

        $fieldCode = $sourceField->getCode();
        $fieldType = Mapping\FieldInterface::FIELD_TYPE_TEXT;

        $path = $sourceField->getNestedPath();

        $fieldConfig = [
            'is_searchable' => $sourceField->getIsSearchable(),
            'is_used_in_spellcheck' => $sourceField->getIsSpellchecked(),
            'is_filterable' => $sourceField->getIsFilterable() || $sourceField->getIsUsedForRules() || $sourceField->getIsUsedInAutocomplete(),
            'search_weight' => $sourceField->getWeight(),
            'is_used_for_sort_by' => $sourceField->getIsSortable(),
            'is_spannable' => $sourceField->getIsSpannable(),
            'default_search_analyzer' => $sourceField->getDefaultSearchAnalyzer(),
        ];

        $fields[$fieldCode] = new Mapping\Field($fieldCode, $fieldType, $path, $fieldConfig);

        if (empty($path)) {
            $fieldCode = \sprintf('children.%s', $sourceField->getCode());
            // Flag explicitly the field as non-nested.
            $fields[$fieldCode] = new Mapping\Field($fieldCode, $fieldType, null, $fieldConfig);
        }

        return $fields;
    }
}
