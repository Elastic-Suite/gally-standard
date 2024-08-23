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

namespace Gally\Metadata\GraphQl\Type\Definition\Filter;

use Gally\Metadata\Model\SourceField;
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
                FilterOperator::GTE => Type::float(),
                FilterOperator::GT => Type::float(),
                FilterOperator::LT => Type::float(),
                FilterOperator::LTE => Type::float(),
            ],
        ];
    }

    public function transformToGallyFilter(array $inputFilter, ContainerConfigurationInterface $containerConfig, array $filterContext = []): QueryInterface
    {
        return $this->distanceFilterInputType->transformToGallyFilter($inputFilter, $containerConfig, $filterContext);
    }
}
