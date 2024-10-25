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
use Gally\Search\Elasticsearch\Builder\Request\Query\Filter\FilterQueryBuilder;
use Gally\Search\Elasticsearch\Request\ContainerConfigurationInterface;
use Gally\Search\Elasticsearch\Request\QueryFactory;
use Gally\Search\Elasticsearch\Request\QueryInterface;
use Gally\Search\GraphQl\Type\Definition\Filter\DistanceFilterInputType;
use GraphQL\Type\Definition\Type;

class LocationTypeFilterInputType extends FloatTypeFilterInputType
{
    public const NAME = 'LocationTypeFilterInputType';

    public string $name = self::NAME;

    public function __construct(
        FilterQueryBuilder $filterQueryBuilder,
        QueryFactory $queryFactory,
        private DistanceFilterInputType $distanceFilterInputType,
        string $nestingSeparator,
    ) {
        parent::__construct($filterQueryBuilder, $queryFactory, $nestingSeparator);
    }

    public function supports(SourceField $sourceField): bool
    {
        return SourceField\Type::TYPE_LOCATION === $sourceField->getType();
    }

    public function getConfig(): array
    {
        return [
            'fields' => [
                FilterOperator::EQ => Type::string(),
                FilterOperator::IN => Type::listOf(Type::string()),
                FilterOperator::GTE => Type::float(),
                FilterOperator::GT => Type::float(),
                FilterOperator::LT => Type::float(),
                FilterOperator::LTE => Type::float(),
            ],
        ];
    }

    public function validate(string $argName, mixed $inputData, ContainerConfigurationInterface $containerConfig): array
    {
        $errors = [];

        if (empty($inputData)) {
            $errors[] = \sprintf(
                "Filter argument %s: At least '%s', '%s', '%s', '%s', '%s', '%s' or '%s' should be filled.",
                $argName,
                FilterOperator::EQ,
                FilterOperator::IN,
                FilterOperator::GTE,
                FilterOperator::GT,
                FilterOperator::LT,
                FilterOperator::LTE,
                FilterOperator::EXIST,
            );
        }

        if (isset($inputData[FilterOperator::GT]) && isset($inputData[FilterOperator::GTE])) {
            $errors[] = \sprintf(
                "Filter argument %s: Do not use '%s' and '%s' in the same filter.",
                $argName,
                FilterOperator::GT,
                FilterOperator::GTE,
            );
        }

        if (isset($inputData[FilterOperator::LT]) && isset($inputData[FilterOperator::LTE])) {
            $errors[] = \sprintf(
                "Filter argument %s: Do not use '%s' and '%s' in the same filter.",
                $argName,
                FilterOperator::LT,
                FilterOperator::LTE,
            );
        }

        return $errors;
    }

    public function transformToGallyFilter(array $inputFilter, ContainerConfigurationInterface $containerConfig, array $filterContext = []): QueryInterface
    {
        return $this->distanceFilterInputType->transformToGallyFilter($inputFilter, $containerConfig, $filterContext);
    }
}
