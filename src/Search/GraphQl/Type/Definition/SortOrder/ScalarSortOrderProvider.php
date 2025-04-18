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

namespace Gally\Search\GraphQl\Type\Definition\SortOrder;

use Gally\Metadata\Entity\SourceField;
use Gally\Metadata\Entity\SourceField\Type;

class ScalarSortOrderProvider implements SortOrderProviderInterface
{
    public function __construct(protected string $nestingSeparator)
    {
    }

    public function supports(SourceField $sourceField): bool
    {
        return \in_array(
            $sourceField->getType(),
            [
                Type::TYPE_TEXT,
                Type::TYPE_KEYWORD,
                Type::TYPE_INT,
                Type::TYPE_BOOLEAN,
                Type::TYPE_FLOAT,
                Type::TYPE_REFERENCE,
                Type::TYPE_DATE,
            ], true
        );
    }

    public function getSortOrderField(SourceField $sourceField): string
    {
        return str_replace('.', $this->nestingSeparator, $sourceField->getCode());
    }

    public function getLabel(string $code, string $label): string
    {
        return \sprintf('Sorting by %s (%s)', $label, $code);
    }

    public function getSimplifiedLabel(SourceField $sourceField): string
    {
        return $sourceField->getDefaultLabel();
    }
}
