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

class FileSourceFieldConverter implements SourceFieldConverterInterface
{
    public function supports(SourceField $sourceField): bool
    {
        return SourceField\Type::TYPE_FILE === $sourceField->getType();
    }

    public function getFields(SourceField $sourceField): array
    {
        $fields = [];

        $fieldCode = $sourceField->getCode();
        $fieldType = Mapping\FieldInterface::FIELD_TYPE_TEXT;

        $path = $sourceField->getNestedPath();

        $fields[$fieldCode] = new Mapping\Field($fieldCode, $fieldType, $path, []);
        $fields[$fieldCode . '_content.content'] = new Mapping\Field(
            $fieldCode . '_content.content',
            $fieldType,
            $path ? "$path.{$fieldCode}_content" : "{$fieldCode}_content",
            [
                'is_searchable' => $sourceField->getIsSearchable(),
                'is_used_in_spellcheck' => $sourceField->getIsSpellchecked(),
                'is_filterable' => false,
                'search_weight' => $sourceField->getWeight(),
                'is_used_for_sort_by' => false,
            ]
        );

        return $fields;
    }
}
