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

namespace Gally\Search\Service;

use Gally\Category\Entity\Category;

/**
 * ViewMore context. Used as a singleton to pass filter name to the aggregation modifier.
 */
class SearchContext
{
    private ?Category $category = null;
    private ?string $searchQueryText = null;
    private ?string $priceGroup = null;
    private ?string $referenceLocation = null;
    private array $contextData = [];

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): void
    {
        $this->category = $category;
    }

    public function getSearchQueryText(): ?string
    {
        return $this->searchQueryText;
    }

    public function setSearchQueryText(?string $searchQueryText): void
    {
        $this->searchQueryText = $searchQueryText;
    }

    public function getPriceGroup(): ?string
    {
        return $this->priceGroup;
    }

    public function setPriceGroup(?string $priceGroup): void
    {
        $this->priceGroup = $priceGroup;
    }

    public function getReferenceLocation(): ?string
    {
        return $this->referenceLocation;
    }

    public function setReferenceLocation(?string $referenceLocation): void
    {
        $this->referenceLocation = $referenceLocation;
    }

    public function getContextData(string $key, $default = null): mixed
    {
        return $this->contextData[$key] ?? $default;
    }

    public function addContextData(string $key, mixed $data): void
    {
        $this->contextData[$key] = $data;
    }

    public function removeContextData(string $key): void
    {
        unset($this->contextData[$key]);
    }
}
