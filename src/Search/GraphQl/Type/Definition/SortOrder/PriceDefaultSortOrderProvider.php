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

class PriceDefaultSortOrderProvider implements SortOrderProviderInterface
{
    public function __construct(protected string $nestingSeparator)
    {
    }

    public function supports(SourceField $sourceField): bool
    {
        return Type::TYPE_PRICE === $sourceField->getType();
    }

    public function getSortOrderField(SourceField $sourceField): string
    {
        return str_replace(
            '.',
            $this->nestingSeparator,
            \sprintf('%s.%s', $sourceField->getCode(), 'price'),
        );
    }

    public function getLabel(string $code, string $label): string
    {
        return \sprintf("Sorting by %s's final price (%s)", $label, $code);
    }

    public function getSimplifiedLabel(SourceField $sourceField): string
    {
        return 'Price';
    }
}
