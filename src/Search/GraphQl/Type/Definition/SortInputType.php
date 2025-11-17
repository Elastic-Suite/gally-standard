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

namespace Gally\Search\GraphQl\Type\Definition;

use ApiPlatform\GraphQl\Type\Definition\TypeInterface;
use Gally\Metadata\Entity\Metadata;
use Gally\Metadata\Entity\SourceField\Type;
use Gally\Search\Elasticsearch\Request\ContainerConfigurationInterface;
use Gally\Search\Elasticsearch\Request\SortOrderInterface;
use Gally\Search\Service\ReverseSourceFieldProvider;
use Gally\Search\Service\SearchContext;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;

class SortInputType extends InputObjectType implements TypeInterface
{
    public const NAME = 'SortInput';

    public function __construct(
        private TypeInterface $sortEnumType,
        protected SearchContext $searchContext,
        protected ReverseSourceFieldProvider $reverseSourceFieldProvider,
    ) {
        $this->name = self::NAME;

        parent::__construct($this->getConfig());
    }

    public function getConfig(): array
    {
        return [
            'fields' => [
                'field' => GraphQLType::nonNull(GraphQLType::string()),
                'direction' => $this->sortEnumType,
            ],
        ];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function formatSort(ContainerConfigurationInterface $containerConfig, mixed $context, Metadata $metadata): ?array
    {
        if (!\array_key_exists('sort', $context['filters'])) {
            return $containerConfig->getDefaultSortingOption();
        }

        $field = $context['filters']['sort']['field'];
        $direction = $context['filters']['sort']['direction'] ?? SortOrderInterface::DEFAULT_SORT_DIRECTION;

        return $this->addNestedFieldData([$field => ['direction' => $direction]], $metadata);
    }

    public function addNestedFieldData(array $sortOrders, Metadata $metadata): array
    {
        foreach ($sortOrders as $sortField => &$sortParams) {
            $sourceField = $this->reverseSourceFieldProvider->getSourceFieldFromFieldName($sortField, $metadata);

            if (Type::TYPE_PRICE == $sourceField?->getType()) {
                $sortParams['nestedPath'] = $sourceField->getCode();
                $sortParams['nestedFilter'] = [$sourceField->getCode() . '.group_id' => $this->searchContext->getPriceGroup()];
            }

            if (Type::TYPE_CATEGORY == $sourceField?->getType()) {
                $sortParams['nestedPath'] = $sourceField->getCode();
                $sortParams['nestedFilter'] = [$sourceField->getCode() . '.id' => $this->searchContext->getCategory()?->getId()];
            }

            if (Type::TYPE_LOCATION == $sourceField?->getType()) {
                $sortParams['referenceLocation'] = $this->searchContext->getReferenceLocation();
            }
        }

        return $sortOrders;
    }
}
