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

use Gally\Metadata\Entity\SourceField;
use Gally\Search\Constant\FilterOperator;
use Gally\Search\Elasticsearch\Request\ContainerConfigurationInterface;
use GraphQL\Type\Definition\Type;

class TextTypeFilterInputType extends AbstractFilter
{
    public const NAME = 'EntityTextTypeFilterInput';

    public string $name = self::NAME;

    public function supports(SourceField $sourceField): bool
    {
        return \in_array(
            $sourceField->getType(),
            [
                SourceField\Type::TYPE_TEXT,
                SourceField\Type::TYPE_KEYWORD,
                SourceField\Type::TYPE_REFERENCE,
            ], true
        );
    }

    public function getConfig(): array
    {
        return [
            'fields' => [
                FilterOperator::EQ => Type::string(),
                FilterOperator::IN => Type::listOf(Type::string()),
                FilterOperator::MATCH => Type::string(),
                FilterOperator::EXIST => Type::boolean(),
            ],
        ];
    }

    public function validate(string $argName, mixed $inputData, ContainerConfigurationInterface $containerConfig): array
    {
        $errors = [];

        if (\count($inputData) < 1) {
            $errors[] = \sprintf(
                "Filter argument %s: At least '%s', '%s', '%s' or '%s' should be filled.",
                $argName,
                FilterOperator::EQ,
                FilterOperator::IN,
                FilterOperator::MATCH,
                FilterOperator::EXIST,
            );
        }

        if (\count($inputData) > 1) {
            $errors[] = \sprintf(
                "Filter argument %s: Only '%s', '%s', '%s' or '%s' should be filled.",
                $argName,
                FilterOperator::EQ,
                FilterOperator::IN,
                FilterOperator::MATCH,
                FilterOperator::EXIST,
            );
        }

        return $errors;
    }
}
