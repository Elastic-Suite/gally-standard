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

namespace Gally\Metadata\GraphQl\Type\Definition\Filter;

use Gally\Metadata\Model\SourceField;

class StockTypeDefaultFilterInputType extends BoolTypeFilterInputType
{
    public const SPECIFIC_NAME = 'StockTypeDefaultFilterInputType';

    public string $name = self::SPECIFIC_NAME;

    public function supports(SourceField $sourceField): bool
    {
        return SourceField\Type::TYPE_STOCK === $sourceField->getType();
    }

    public function getFilterFieldName(string $sourceFieldCode): string
    {
        return $sourceFieldCode . '.status';
    }
}
