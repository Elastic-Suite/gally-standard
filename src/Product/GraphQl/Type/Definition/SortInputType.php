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

namespace Gally\Product\GraphQl\Type\Definition;

use ApiPlatform\Core\GraphQl\Type\Definition\TypeInterface;
use Gally\Category\Service\CurrentCategoryProvider;
use Gally\Entity\Service\PriceGroupProvider;
use Gally\Metadata\Model\Metadata;
use Gally\Metadata\Repository\SourceFieldRepository;
use Gally\Product\GraphQl\Type\Definition\SortOrder\SortOrderProviderInterface;
use Gally\Search\Elasticsearch\Request\ContainerConfigurationInterface;
use Gally\Search\Elasticsearch\Request\SortOrderInterface;
use Gally\Search\GraphQl\Type\Definition\SortInputType as SearchSortInputType;
use Gally\Search\Service\ReverseSourceFieldProvider;

class SortInputType extends SearchSortInputType
{
    public const NAME = 'ProductSortInput';

    public function __construct(
        private TypeInterface $sortEnumType,
        private SourceFieldRepository $sourceFieldRepository,
        private iterable $sortOrderProviders,
        protected PriceGroupProvider $priceGroupProvider,
        protected ReverseSourceFieldProvider $reverseSourceFieldProvider,
        protected CurrentCategoryProvider $currentCategoryProvider,
        private string $nestingSeparator,
    ) {
        parent::__construct($this->sortEnumType, $this->priceGroupProvider, $this->reverseSourceFieldProvider);
        $this->name = self::NAME;
    }

    public function getConfig(): array
    {
        $fields = [];
        foreach ($this->sourceFieldRepository->getSortableFields('product') as $sortableField) {
            /** @var SortOrderProviderInterface $sortOrderProvider */
            foreach ($this->sortOrderProviders as $sortOrderProvider) {
                if ($sortOrderProvider->supports($sortableField)) {
                    $fieldName = $sortOrderProvider->getSortOrderField($sortableField);
                    $fields[$fieldName] = [
                        'type' => $this->sortEnumType,
                        'description' => $sortOrderProvider->getLabel($sortableField),
                    ];
                }
            }
        }

        $fields[SortOrderInterface::DEFAULT_SORT_FIELD] = [
            'type' => $this->sortEnumType,
            'description' => 'Product relevance according to context (_score)',
        ];

        return ['fields' => $fields];
    }

    public function validateSort(array &$context): void
    {
        if (!\array_key_exists('sort', $context['filters'])) {
            return;
        }

        foreach (array_keys($context['filters']['sort']) as $field) {
            if (str_contains($field, $this->nestingSeparator)) {
                unset($context['filters']['sort'][$field]);
            }
        }

        if (\count($context['filters']['sort']) > 1) {
            throw new \InvalidArgumentException('Sort argument : You can\'t sort on multiple attribute.');
        }
    }

    public function formatSort(ContainerConfigurationInterface $containerConfig, mixed $context, Metadata $metadata): ?array
    {
        if (!\array_key_exists('sort', $context['filters'])) {
            $sortOrders = $containerConfig->getDefaultSortingOption();
        } else {
            $sortOrders = array_map(
                fn ($direction) => ['direction' => $direction],
                $context['filters']['sort']
            );
        }

        return $this->addNestedFieldData($sortOrders, $metadata);
    }
}
