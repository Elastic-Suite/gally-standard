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
use Gally\Search\Elasticsearch\Request\ContainerConfigurationInterface;
use GraphQL\Type\Definition\Type;

class DateTypeFilterInputType extends AbstractFilter
{
    public const NAME = 'EntityDateTypeFilterInput';

    public $name = self::NAME;

    public function supports(SourceField $sourceField): bool
    {
        return \in_array(
            $sourceField->getType(),
            [
                SourceField\Type::TYPE_DATE,
            ], true
        );
    }

    public function getConfig(): array
    {
        return [
            'fields' => [
                FilterOperator::GTE => Type::string(),
                FilterOperator::GT => Type::string(),
                FilterOperator::LT => Type::string(),
                FilterOperator::LTE => Type::string(),
                FilterOperator::EXIST => Type::boolean(),
            ],
        ];
    }

    public function validate(string $argName, mixed $inputData, ContainerConfigurationInterface $containerConfig): array
    {
        $errors = [];

        if (empty($inputData)) {
            $errors[] = sprintf(
                "Filter argument %s: At least '%s', '%s', '%s', '%s' or '%s' should be filled.",
                $argName,
                FilterOperator::GTE,
                FilterOperator::GT,
                FilterOperator::LT,
                FilterOperator::LTE,
                FilterOperator::EXIST,
            );
        }

        if (isset($inputData[FilterOperator::GT]) && isset($inputData[FilterOperator::GTE])) {
            $errors[] = sprintf(
                "Filter argument %s: Do not use '%s' and '%s' in the same filter.",
                $argName,
                FilterOperator::GT,
                FilterOperator::GTE,
            );
        }

        if (isset($inputData[FilterOperator::LT]) && isset($inputData[FilterOperator::LTE])) {
            $errors[] = sprintf(
                "Filter argument %s: Do not use '%s' and '%s' in the same filter.",
                $argName,
                FilterOperator::LT,
                FilterOperator::LTE,
            );
        }

        return $errors;
    }
}
