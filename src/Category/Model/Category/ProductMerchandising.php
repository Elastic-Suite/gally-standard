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

namespace Gally\Category\Model\Category;

use ApiPlatform\Action\NotFoundAction;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use Gally\Catalog\Model\Catalog;
use Gally\Catalog\Model\LocalizedCatalog;
use Gally\Category\Controller\CategoryProductPositionGet;
use Gally\Category\Controller\CategoryProductPositionSave;
use Gally\Category\Model\Category;
use Gally\Category\Resolver\PositionGetResolver;
use Gally\Category\Resolver\PositionSaveResolver;
use Gally\User\Constant\Role;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new Get(
            controller: NotFoundAction::class,
            read: false,
            output: false
        ),
        new Get(
            security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')",
            uriTemplate: '/category_product_merchandisings/getPositions/{categoryId}/{localizedCatalogId}',
            controller: CategoryProductPositionGet::class,
            read: false,
            deserialize: false,
            validate: false,
            write: false,
            serialize: true,
            status: Response::HTTP_OK,
            normalizationContext: ['groups' => ['category_product_merchandising_result:read']],
            openapiContext: [
                'summary' => 'Get product positions in a category.',
                'description' => 'Get product positions in a category.',
                'parameters' => [
                    ['name' => 'categoryId', 'in' => 'path', 'type' => 'string', 'required' => true],
                    ['name' => 'localizedCatalogId', 'in' => 'path', 'type' => 'int', 'required' => true],
                ],
            ]),
        new Post(
            security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')",
            uriTemplate: '/category_product_merchandisings/savePositions/{categoryId}',
            uriVariables: [
                'categoryId' => new Link(
                    fromClass: Category::class,
                    fromProperty: 'id'
                ),
            ],
            controller: CategoryProductPositionSave::class,
            read: false,
            deserialize: false,
            validate: false,
            write: false,
            serialize: true,
            status: Response::HTTP_OK,
            normalizationContext: ['groups' => ['category_product_merchandising_result:read']],
            openapiContext: [
                'summary' => 'Save product positions in a category.',
                'description' => 'Save product positions in a category.',
                'parameters' => [
                    ['name' => 'categoryId', 'in' => 'path', 'type' => 'Category', 'required' => true],
                ],
                'requestBody' => [
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'catalogId' => ['type' => 'string'],
                                    'localizedCatalogId' => ['type' => 'string'],
                                    'positions' => ['type' => 'string'],
                                ],
                            ],
                            'example' => [
                                'catalogId' => 'string',
                                'localizedCatalogId' => 'string',
                                'positions' => '[{"productId": 1, "position": 10}, {"productId": 2, "position": 20}]',
                            ],
                        ],
                    ],
                ],
            ]
        )],
    graphQlOperations: [
        new Mutation(
            name: 'savePositions',
            resolver: PositionSaveResolver::class,
            args: [
                'categoryId' => ['type' => 'String!'],
                'catalogId' => ['type' => 'Int'],
                'localizedCatalogId' => ['type' => 'Int'],
                'positions' => ['type' => 'String!'],
            ],
            security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')",
            read: false,
            deserialize: false,
            write: false,
            serialize: true,
            normalizationContext: ['groups' => ['category_product_merchandising_result:read']]
        ),
        new Query(
            name: 'getPositions',
            resolver: PositionGetResolver::class,
            args: [
                'categoryId' => ['type' => 'String!'],
                'localizedCatalogId' => ['type' => 'Int!'],
            ],
            security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')",
            read: false,
            deserialize: false,
            write: false,
            serialize: true,
            normalizationContext: ['groups' => ['category_product_merchandising_result:read']]
        ),
    ],
    shortName: 'CategoryProductMerchandising',
    denormalizationContext: ['groups' => ['category_product_merchandising:write']],
    normalizationContext: ['groups' => ['category_product_merchandising:read']],
    paginationEnabled: false
)]
class ProductMerchandising
{
    #[Groups(['category_product_merchandising:read', 'category_product_merchandising:write'])]
    private int $id;

    #[Groups(['category_product_merchandising:read', 'category_product_merchandising:write'])]
    private Category $category;

    #[Groups(['category_product_merchandising:read', 'category_product_merchandising:write'])]
    private string $productId;

    #[Groups(['category_product_merchandising:read', 'category_product_merchandising:write'])]
    private ?Catalog $catalog = null;

    #[Groups(['category_product_merchandising:read', 'category_product_merchandising:write'])]
    private ?LocalizedCatalog $localizedCatalog = null;

    #[Groups(['category_product_merchandising:read', 'category_product_merchandising:write'])]
    private ?int $position;

    #[Groups(['category_product_merchandising_result:read'])]
    // This property is used to send a result in "category_position_save" endpoints (rest + graphql) as we can't return a position.
    // We use a property because it's not possible to use output with mutation.
    // @see https://github.com/api-platform/core/issues/3155
    private string $result = 'OK';

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

    public function getProductId(): ?string
    {
        return $this->productId;
    }

    public function setProductId(?string $productId): void
    {
        $this->productId = $productId;
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

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): void
    {
        $this->position = $position;
    }

    public function getResult(): string
    {
        return $this->result;
    }

    public function setResult(string $result): void
    {
        $this->result = $result;
    }
}
