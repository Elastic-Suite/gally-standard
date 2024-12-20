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
use Gally\GraphQl\Type\Definition\FilterInterface;
use Gally\Search\Elasticsearch\Request\ContainerConfigurationInterface;
use Gally\Search\Elasticsearch\Request\QueryFactory;
use Gally\Search\Elasticsearch\Request\QueryInterface;
use Gally\Search\GraphQl\Type\Definition\FieldFilterInputType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

class BoolFilterInputType extends InputObjectType implements TypeInterface, FilterInterface
{
    public const NAME = 'BoolFilterInput';

    public string $name = self::NAME;

    private array $mappedBooleanConditions = [
        '_must' => 'must',
        '_should' => 'should',
        '_not' => 'mustNot',
    ];

    public function __construct(
        private FieldFilterInputType $fieldFilterInputType,
        private QueryFactory $queryFactory,
    ) {
        parent::__construct($this->getConfig());
    }

    public function getConfig(): array
    {
        return [
            'fields' => [
                '_must' => fn () => Type::listOf($this->fieldFilterInputType),
                '_should' => fn () => Type::listOf($this->fieldFilterInputType),
                '_not' => fn () => Type::listOf($this->fieldFilterInputType),
            ],
        ];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function validate(string $argName, mixed $inputData, ContainerConfigurationInterface $containerConfig): array
    {
        $errors = [];

        if (isset($inputData['_must'])) {
            $errors = array_merge($errors, $this->fieldFilterInputType->validate($argName, $inputData['_must'], $containerConfig));
        }

        if (isset($inputData['_should'])) {
            $errors = array_merge($errors, $this->fieldFilterInputType->validate($argName, $inputData['_should'], $containerConfig));
        }

        if (isset($inputData['_not'])) {
            $errors = array_merge($errors, $this->fieldFilterInputType->validate($argName, $inputData['_not'], $containerConfig));
        }

        return $errors;
    }

    public function transformToGallyFilter(array $inputFilter, ContainerConfigurationInterface $containerConfig, array $filterContext = []): QueryInterface
    {
        $queryParams = [];
        foreach (array_keys($this->mappedBooleanConditions) as $param) {
            if (isset($inputFilter[$param])) {
                $queries = [];
                foreach ($inputFilter[$param] as $filter) {
                    $queries[] = $this->fieldFilterInputType->transformToGallyFilter($filter, $containerConfig, $filterContext);
                }
                $queryParams[$this->mappedBooleanConditions[$param]] = $queries;
            }
        }

        return $this->queryFactory->create(QueryInterface::TYPE_BOOL, $queryParams);
    }
}
