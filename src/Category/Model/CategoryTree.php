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

namespace Gally\Category\Model;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GraphQl\Query;
use Gally\Category\Controller\GetCategoryTree;
use Gally\Category\Resolver\CategoryTreeResolver;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/categoryTree',
            controller: GetCategoryTree::class,
            read: false,
            openapiContext: [
                'parameters' => [
                    ['name' => 'catalogId', 'in' => 'query', 'type' => 'int'],
                    ['name' => 'localizedCatalogId', 'in' => 'query', 'type' => 'int'],
                ],
            ],
        ),
    ],
    graphQlOperations: [
        new Query(
            name: 'get',
            resolver: CategoryTreeResolver::class,
            args: [
                'catalogId' => ['type' => 'Int'],
                'localizedCatalogId' => ['type' => 'Int'],
            ],
        ),
    ],
    extraProperties: [
        'gally' => [
            'cache_tag' => ['resource_classes' => [Category::class, Category\Configuration::class]],
        ],
    ],
    paginationEnabled: false,
)]
class CategoryTree
{
    public function __construct(
        #[ApiProperty(identifier: true)]
        private ?int $catalogId,
        #[ApiProperty(identifier: true)]
        private ?int $localizedCatalogId,
        private array $categories,
    ) {
    }

    public function getCatalogId(): ?int
    {
        return $this->catalogId;
    }

    public function getLocalizedCatalogId(): ?int
    {
        return $this->localizedCatalogId;
    }

    public function getCategories(): array
    {
        return $this->categories;
    }
}
