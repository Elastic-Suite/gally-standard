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

namespace Gally\Product\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use ApiPlatform\State\ProviderInterface;
use Gally\Product\Entity\Source\ProductSortingOption;
use Gally\Product\Service\ProductsSortingOptionsProvider;

class ProductSortingOptionProvider implements ProviderInterface
{
    public function __construct(
        private ProductsSortingOptionsProvider $sortingOptionsProvider,
        private ProviderInterface $itemProvider,
    ) {
    }

    /**
     * @return ProductSortingOption|PartialPaginatorInterface<ProductSortingOption>|iterable<ProductSortingOption>|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            return $this->sortingOptionsProvider->getAllSortingOptions();
        }

        return $this->itemProvider->provide($operation, $uriVariables, $context);
    }
}
