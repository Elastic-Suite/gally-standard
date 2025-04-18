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

use Gally\Search\GraphQl\Type\Definition\Filter\BoolFilterInputType as BaseBoolFilterInputType;

class BoolFilterInputType extends BaseBoolFilterInputType
{
    public const NAME = 'EntityBoolFilterInput';

    public string $name = self::NAME;

    public function getGraphQlFilter(array $fields): array
    {
        return [
            'boolFilter' => $fields,
        ];
    }
}
