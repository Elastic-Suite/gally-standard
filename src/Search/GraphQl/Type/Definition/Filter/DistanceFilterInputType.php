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

namespace Gally\Search\GraphQl\Type\Definition\Filter;

use ApiPlatform\GraphQl\Type\Definition\TypeInterface;
use Gally\Configuration\Service\ConfigurationManager;
use Gally\GraphQl\Type\Definition\FilterInterface;
use Gally\Index\Entity\Index\Mapping\FieldInterface;
use Gally\Metadata\Entity\SourceField\Type as SourceFieldType;
use Gally\Search\Constant\FilterOperator;
use Gally\Search\Elasticsearch\Builder\Request\Query\Filter\FilterQueryBuilder;
use Gally\Search\Elasticsearch\Request\ContainerConfigurationInterface;
use Gally\Search\Elasticsearch\Request\QueryFactory;
use Gally\Search\Elasticsearch\Request\QueryInterface;
use Gally\Search\Service\DateFormatUtils;
use Gally\Search\Service\ReverseSourceFieldProvider;
use GraphQL\Type\Definition\Type;

class DistanceFilterInputType extends RangeFilterInputType implements TypeInterface, FilterInterface
{
    use FilterableFieldTrait;

    public const NAME = 'DistanceFilterInput';

    public function __construct(
        private FilterQueryBuilder $filterQueryBuilder,
        private ReverseSourceFieldProvider $reverseSourceFieldProvider,
        private QueryFactory $queryFactory,
        ConfigurationManager $configurationManager,
        DateFormatUtils $dateUtils,
    ) {
        parent::__construct($filterQueryBuilder, $reverseSourceFieldProvider, $configurationManager, $dateUtils);
        $this->name = self::NAME;
    }

    public function getConfig(): array
    {
        return [
            'fields' => [
                'field' => ['type' => Type::nonNull(Type::string())],
                FilterOperator::EQ => Type::string(),
                FilterOperator::IN => Type::listOf(Type::string()),
                FilterOperator::GTE => Type::float(),
                FilterOperator::LTE => Type::float(),
                FilterOperator::GT => Type::float(),
                FilterOperator::LT => Type::float(),
            ],
        ];
    }

    public function validate(string $argName, mixed $inputData, $containerConfig): array
    {
        $errors = parent::validateIsFilterable($inputData['field'], $containerConfig);

        $field = $this->reverseSourceFieldProvider->getSourceFieldFromFieldName(
            $inputData['field'],
            $containerConfig->getMetadata()
        );
        if ($field && SourceFieldType::TYPE_LOCATION !== $field->getType()) {
            $errors[] = \sprintf(
                'Filter argument %s: The field %s should be of type %s.',
                $argName,
                $field->getCode(),
                FieldInterface::FIELD_TYPE_GEOPOINT
            );
        }

        if (isset($inputData[FilterOperator::EQ]) && !str_contains($inputData[FilterOperator::EQ], '-')) {
            $errors[] = \sprintf(
                'Filter argument %s: The value must be a range and contain a hyphen (\'-\').',
                $argName
            );
            if (\count($inputData) > 2) {
                $errors[] = \sprintf(
                    'Filter argument %s: The \'in\' operator cannot be combined with other operators.',
                    $argName
                );
            }
        }

        if (isset($inputData[FilterOperator::IN])) {
            foreach ($inputData[FilterOperator::IN] as $value) {
                if (!str_contains($value, '-')) {
                    $errors[] = \sprintf(
                        'Filter argument %s: The value must be a range and contain a hyphen (\'-\').',
                        $argName
                    );
                }
            }
            if (\count($inputData) > 2) {
                $errors[] = \sprintf(
                    'Filter argument %s: The \'in\' operator cannot be combined with other operators.',
                    $argName
                );
            }
        }

        return $errors;
    }

    public function transformToGallyFilter(array $inputFilter, ContainerConfigurationInterface $containerConfig, array $filterContext = []): QueryInterface
    {
        $queryParams = [
            'must' => [$this->queryFactory->create(QueryInterface::TYPE_EXISTS, ['field' => $inputFilter['field']])],
        ];

        if (isset($inputFilter[FilterOperator::EQ])) {
            $range = explode('-', $inputFilter[FilterOperator::EQ]);
            if ('*' != $range[0]) {
                $inputFilter[FilterOperator::GTE] = (float) $range[0];
            }
            if ('*' != $range[1]) {
                $inputFilter[FilterOperator::LTE] = (float) $range[1];
            }
            unset($inputFilter[FilterOperator::EQ]);
        }
        if (isset($inputFilter[FilterOperator::IN])) {
            $queries = [];
            $inputFilterEq = ['field' => $inputFilter['field']];
            foreach ($inputFilter[FilterOperator::IN] as $value) {
                $inputFilterEq[FilterOperator::EQ] = $value;
                $queries[] = $this->transformToGallyFilter($inputFilterEq, $containerConfig, $filterContext);
            }
            $queryParams['should'] = $queries;
        }
        if (isset($inputFilter[FilterOperator::GT])) {
            $queryParams['mustNot'][] = $this->filterQueryBuilder->create(
                $containerConfig,
                [$inputFilter['field'] => [FilterOperator::LTE => $inputFilter[FilterOperator::GT]]]
            );
        }
        if (isset($inputFilter[FilterOperator::GTE])) {
            $queryParams['mustNot'][] = $this->filterQueryBuilder->create(
                $containerConfig,
                [$inputFilter['field'] => [FilterOperator::LTE => $inputFilter[FilterOperator::GTE] + 0.0001]]
            );
        }
        if (isset($inputFilter[FilterOperator::LTE])) {
            $queryParams['must'][] = $this->filterQueryBuilder->create(
                $containerConfig,
                [$inputFilter['field'] => [FilterOperator::LTE => $inputFilter[FilterOperator::LTE]]]
            );
        }
        if (isset($inputFilter[FilterOperator::LT])) {
            $queryParams['must'][] = $this->filterQueryBuilder->create(
                $containerConfig,
                [$inputFilter['field'] => [FilterOperator::LTE => $inputFilter[FilterOperator::LT] - 0.0001]]
            );
        }

        return $this->queryFactory->create(QueryInterface::TYPE_BOOL, $queryParams);
    }
}
