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

namespace Gally\Product\Service;

use Gally\Search\Service\SortingOptionsProvider;

class ProductsSortingOptionsProvider
{
    public function __construct(
        private SortingOptionsProvider $sortingOptionsProvider
    ) {
    }

    /**
     * Return all products sorting options for categories.
     */
    public function getAllSortingOptions(): array
    {
        return $this->sortingOptionsProvider->getAllSortingOptions('product');
    }
}
