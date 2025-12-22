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

namespace Gally\Category\Entity\Category;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model;
use Gally\Catalog\Entity\Catalog;
use Gally\Catalog\Entity\LocalizedCatalog;
use Gally\Category\Controller\CategoryConfigurationGet;
use Gally\Category\Entity\Category;
use Gally\Category\Resolver\ConfigurationResolver;
use Gally\User\Constant\Role;

#[ApiResource(
    operations: [
        new Get(security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
        new Get(
            security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')",
            uriTemplate: '/category_configurations/category/{categoryId}',
            uriVariables: [
                'categoryId' => new Link(
                    fromClass: Category::class,
                    fromProperty: 'id'
                ),
            ],
            controller: CategoryConfigurationGet::class,
            read: false,
            serialize: false,
            openapi: new Model\Operation(
                parameters: [
                    new Model\Parameter(
                        name: 'categoryId',
                        in: 'path',
                        required: true,
                        schema: ['type' => 'string'],
                    ),
                    new Model\Parameter(
                        name: 'catalogId',
                        in: 'query',
                        required: false,
                        schema: ['type' => 'integer'],
                    ),
                    new Model\Parameter(
                        name: 'localizedCatalogId',
                        in: 'query',
                        required: false,
                        schema: ['type' => 'integer'],
                    ),
                ],
            ),
        ),
        new Put(security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
        new Patch(security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
        new GetCollection(security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
        new Post(security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
    ],
    graphQlOperations: [
        new Mutation(name: 'update', security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
        new Query(name: 'item_query', security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
        new QueryCollection(name: 'collection_query', security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
        new Query(
            name: 'get',
            resolver: ConfigurationResolver::class,
            security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')",
            args: [
                'categoryId' => ['type' => 'String!'],
                'catalogId' => ['type' => 'Int'],
                'localizedCatalogId' => ['type' => 'Int'],
            ],
        ),
    ],
    shortName: 'CategoryConfiguration',
)]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['category' => 'exact'])]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['catalog' => 'exact'])]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['localized_catalog' => 'exact'])]
class Configuration
{
    private int $id;

    private Category $category;

    private ?Catalog $catalog = null;

    private ?LocalizedCatalog $localizedCatalog = null;

    private ?string $name = null;

    private ?bool $isVirtual = null;

    private ?string $virtualRule = null;

    #[ApiProperty(
        extraProperties: [
            'hydra:supportedProperty' => [
                'gally' => [
                    'depends' => [
                        'type' => 'enabled',
                        'conditions' => [
                            ['field' => 'isVirtual', 'value' => false],
                        ],
                    ],
                ],
            ],
        ],
    )]
    private ?bool $useNameInProductSearch = null;

    #[ApiProperty(
        extraProperties: [
            'hydra:supportedProperty' => [
                'gally' => [
                    'input' => 'select',
                    'options' => [
                        'api_rest' => '/product_sorting_options',
                        'api_graphql' => 'productSortingOptions',
                    ],
                ],
            ],
        ],
    )]
    private ?string $defaultSorting = null;

    private bool $isActive = true;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function setCategory(Category $category): void
    {
        $this->category = $category;
    }

    public function getCatalog(): ?Catalog
    {
        return $this->catalog;
    }

    public function setCatalog(?Catalog $catalog): void
    {
        $this->catalog = $catalog;
    }

    public function getLocalizedCatalog(): ?LocalizedCatalog
    {
        return $this->localizedCatalog;
    }

    public function setLocalizedCatalog(?LocalizedCatalog $localizedCatalog): void
    {
        $this->localizedCatalog = $localizedCatalog;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getUseNameInProductSearch(): bool
    {
        return $this->useNameInProductSearch ?? true;
    }

    public function setUseNameInProductSearch(?bool $useNameInProductSearch): void
    {
        $this->useNameInProductSearch = $useNameInProductSearch;
    }

    public function getIsVirtual(): bool
    {
        return $this->isVirtual ?? false;
    }

    public function setIsVirtual(?bool $isVirtual): void
    {
        $this->isVirtual = $isVirtual;
    }

    public function getVirtualRule(): ?string
    {
        return $this->virtualRule ?? '';
    }

    public function setVirtualRule(?string $virtualRule): void
    {
        $this->virtualRule = $virtualRule;
    }

    public function getDefaultSorting(): string
    {
        return $this->defaultSorting ?? 'category__position';
    }

    public function setDefaultSorting(?string $defaultSorting): void
    {
        $this->defaultSorting = $defaultSorting;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }
}
