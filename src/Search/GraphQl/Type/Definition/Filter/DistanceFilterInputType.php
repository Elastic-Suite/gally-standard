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

namespace Gally\Search\GraphQl\Type\Definition\Filter;

use ApiPlatform\Core\GraphQl\Type\Definition\TypeInterface;
use Gally\GraphQl\Type\Definition\FilterInterface;
use Gally\Index\Model\Index\Mapping\FieldInterface;
use Gally\Metadata\Model\SourceField\Type as SourceFieldType;
use Gally\Search\Constant\FilterOperator;
use Gally\Search\Elasticsearch\Builder\Request\Query\Filter\FilterQueryBuilder;
use Gally\Search\Elasticsearch\Request\ContainerConfigurationInterface;
use Gally\Search\Elasticsearch\Request\QueryInterface;
use Gally\Search\Service\ReverseSourceFieldProvider;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

class DistanceFilterInputType extends InputObjectType implements TypeInterface, FilterInterface
{
    use FilterableFieldTrait;

    public const NAME = 'DistanceFilterInput';

    public function __construct(
        private FilterQueryBuilder $filterQueryBuilder,
        private ReverseSourceFieldProvider $reverseSourceFieldProvider,
    ) {
        $this->name = self::NAME;

        parent::__construct($this->getConfig());
    }

    public function getConfig(): array
    {
        return [
            'fields' => [
                'field' => ['type' => Type::nonNull(Type::string())],
                FilterOperator::LTE => Type::nonNull(Type::float()),
            ],
        ];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function validate(string $argName, mixed $inputData, $containerConfig): array
    {
        $errors = $this->validateIsFilterable($inputData['field'], $containerConfig);

        $field = $this->reverseSourceFieldProvider->getSourceFieldFromFieldName(
            $inputData['field'],
            $containerConfig->getMetadata()
        );
        if ($field && SourceFieldType::TYPE_LOCATION !== $field->getType()) {
            $errors[] = sprintf(
                'Filter argument %s: The field %s should be of type %s.',
                $argName,
                $field->getCode(),
                FieldInterface::FIELD_TYPE_GEOPOINT
            );
        }

        return $errors;
    }

    public function transformToGallyFilter(array $inputFilter, ContainerConfigurationInterface $containerConfig, array $filterContext = []): QueryInterface
    {
        $filterData = [$inputFilter['field'] => [FilterOperator::LTE => $inputFilter[FilterOperator::LTE]]];

        return $this->filterQueryBuilder->create($containerConfig, $filterData);
    }
}
