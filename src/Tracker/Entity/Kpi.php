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

namespace Gally\Tracker\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use Gally\Tracker\State\KpiProvider;
use Gally\User\Constant\Role;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new GetCollection(
            openapiContext: [
                'parameters' => [
                    ['name' => 'localizedCatalog', 'in' => 'query', 'type' => 'string'],
                    ['name' => 'startDate', 'in' => 'query', 'type' => 'string'],
                    ['name' => 'endDate', 'in' => 'query', 'type' => 'string'],
                ],
            ],
        ),
    ],
    graphQlOperations: [
        new QueryCollection(
            name: 'collection_query',
            args: [
                'localizedCatalog' => ['type' => 'String!'],
                'startDate' => ['type' => 'String'],
                'endDate' => ['type' => 'String'],
            ],
        ),
    ],
    security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')",
    provider: KpiProvider::class,
    paginationEnabled: false,
    normalizationContext: ['groups' => ['kpi']]
)]
class Kpi
{
    #[ApiProperty(identifier: true)]
    #[Groups(['kpi'])]
    private string $id;

    #[Groups(['kpi'])]
    private ?string $localizedCatalog = null;

    #[Groups(['kpi'])]
    private ?string $startDate = null;

    #[Groups(['kpi'])]
    private ?string $endDate = null;

    #[Groups(['kpi'])]
    private int $searchCount = 0;

    #[Groups(['kpi'])]
    private int $categoryViewCount = 0;

    #[Groups(['kpi'])]
    private int $productViewCount = 0;

    #[Groups(['kpi'])]
    private int $addToCartCount = 0;

    #[Groups(['kpi'])]
    private int $orderCount = 0;

    #[Groups(['kpi'])]
    private int $sessionCount = 0;

    #[Groups(['kpi'])]
    private int $visitorCount = 0;

    public function __construct()
    {
        $this->id = str_replace('.', '', uniqid('kpi_', true));
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLocalizedCatalog(): ?string
    {
        return $this->localizedCatalog;
    }

    public function setLocalizedCatalog(?string $localizedCatalog): self
    {
        $this->localizedCatalog = $localizedCatalog;

        return $this;
    }

    public function getStartDate(): ?string
    {
        return $this->startDate;
    }

    public function setStartDate(?string $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?string
    {
        return $this->endDate;
    }

    public function setEndDate(?string $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getSearchCount(): int
    {
        return $this->searchCount;
    }

    public function setSearchCount(int $searchCount): self
    {
        $this->searchCount = $searchCount;

        return $this;
    }

    public function getCategoryViewCount(): int
    {
        return $this->categoryViewCount;
    }

    public function setCategoryViewCount(int $categoryViewCount): self
    {
        $this->categoryViewCount = $categoryViewCount;

        return $this;
    }

    public function getProductViewCount(): int
    {
        return $this->productViewCount;
    }

    public function setProductViewCount(int $productViewCount): self
    {
        $this->productViewCount = $productViewCount;

        return $this;
    }

    public function getAddToCartCount(): int
    {
        return $this->addToCartCount;
    }

    public function setAddToCartCount(int $addToCartCount): self
    {
        $this->addToCartCount = $addToCartCount;

        return $this;
    }

    public function getOrderCount(): int
    {
        return $this->orderCount;
    }

    public function setOrderCount(int $orderCount): self
    {
        $this->orderCount = $orderCount;

        return $this;
    }

    public function getSessionCount(): int
    {
        return $this->sessionCount;
    }

    public function setSessionCount(int $sessionCount): self
    {
        $this->sessionCount = $sessionCount;

        return $this;
    }

    public function getVisitorCount(): int
    {
        return $this->visitorCount;
    }

    public function setVisitorCount(int $visitorCount): self
    {
        $this->visitorCount = $visitorCount;

        return $this;
    }
}
