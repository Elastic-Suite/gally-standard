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

namespace Gally\Entity\GraphQl\Type\Definition\Filter;

use Gally\Metadata\Model\SourceField;
use Gally\Search\Constant\FilterOperator;
use GraphQL\Type\Definition\Type;

class LocationTypeFilterInputType extends FloatTypeFilterInputType
{
    public const NAME = 'LocationTypeFilterInputType';

    public $name = self::NAME;

    public function supports(SourceField $sourceField): bool
    {
        return \in_array(
            $sourceField->getType(),
            [SourceField\Type::TYPE_LOCATION],
            true
        );
    }

    public function getConfig(): array
    {
        return [
            'fields' => [
                FilterOperator::LTE => Type::float(),
            ],
        ];
    }

    public function validateValueType(string $field, string $operator, mixed $value): void
    {
        $this->validateValueTypeByType($field, $operator, $value, 'float');
    }
}
