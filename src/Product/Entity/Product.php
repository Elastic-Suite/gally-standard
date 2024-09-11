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

namespace Gally\Product\Entity;

use ApiPlatform\Action\NotFoundAction;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use Gally\Metadata\Entity\Attribute\AttributeInterface;
use Gally\Product\State\ProductProvider;
use Gally\Search\Entity\Document;
use Gally\Search\Resolver\DummyResolver;
use Gally\User\Constant\Role;

#[ApiResource(
    operations: [
        new Get(controller: NotFoundAction::class, read: false, output: false),
    ],
    graphQlOperations: [
        new QueryCollection(
            name: 'search',
            resolver: DummyResolver::class,
            paginationType: 'page',
            args: [
                'localizedCatalog' => [
                    'type' => 'String!',
                    'description' => 'Localized Catalog',
                ],
                'requestType' => [
                    'type' => 'ProductRequestTypeEnum!',
                    'description' => 'Product Request Type',
                ],
                'currentPage' => ['type' => 'Int'],
                'search' => [
                    'type' => 'String',
                    'description' => 'Query Text',
                ],
                'currentCategoryId' => [
                    'type' => 'String',
                    'description' => 'Current category ID',
                ],
                'pageSize' => ['type' => 'Int'],
                'sort' => ['type' => 'ProductSortInput'],
                'filter' => [
                    'type' => '[ProductFieldFilterInput]',
                    'is_gally_arg' => true],
            ],
            read: true,
            deserialize: true,
            write: false,
            serialize: true
        ),
        new QueryCollection(
            name: 'searchPreview',
            resolver: DummyResolver::class,
            paginationType: 'page',
            args: [
                'localizedCatalog' => [
                    'type' => 'String!',
                    'description' => 'Localized Catalog',
                ],
                'requestType' => [
                    'type' => 'ProductRequestTypeEnum!',
                    'description' => 'Request Type',
                ],
                'currentPage' => ['type' => 'Int'],
                'search' => [
                    'type' => 'String',
                    'description' => 'Query Text',
                ],
                'currentCategoryId' => [
                    'type' => 'String',
                    'description' => 'Current category ID',
                ],
                'pageSize' => ['type' => 'Int'],
                'sort' => ['type' => 'ProductSortInput'],
                'filter' => [
                    'type' => '[ProductFieldFilterInput]',
                    'is_gally_arg' => true,
                ],
                'currentCategoryConfiguration' => [
                    'type' => 'String',
                    'description' => 'Current category configuration',
                ],
            ],
            read: true,
            deserialize: true,
            write: false,
            serialize: true,
            security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
    ],
    provider: ProductProvider::class,
    extraProperties: [
        'gally' => [
            'stitching' => ['property' => 'attributes'],
            'metadata' => ['entity' => 'product'],
        ],
    ],
    paginationClientEnabled: true,
    paginationClientItemsPerPage: true,
    paginationClientPartial: false,
    paginationEnabled: true,
    paginationItemsPerPage: 30,
    paginationMaximumItemsPerPage: 100
)]
class Product extends Document
{
    public const DEFAULT_ATTRIBUTES = ['_id', 'id', 'data', 'source', 'index', 'type', 'score'];

    /** @var AttributeInterface[] */
    public array $attributes = [];

    public function addAttribute(AttributeInterface $attribute)
    {
        $this->attributes[$attribute->getAttributeCode()] = $attribute;
    }
}
