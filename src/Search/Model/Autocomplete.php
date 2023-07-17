<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade to newer versions in the future.
 *
 * @package   Elasticsuite
 * @author    ElasticSuite Team <elasticsuite@smile.fr>
 * @copyright 2023 Smile
 * @license   Licensed to Smile-SA. All rights reserved. No warranty, explicit or implicit, provided.
 *            Unauthorized copying of this file, via any medium, is strictly prohibited.
 */

declare(strict_types=1);

namespace Gally\Search\Model;

use ApiPlatform\Core\Action\NotFoundAction;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Gally\GraphQl\Decoration\Resolver\Stage\ReadStage;
use Gally\Category\Model\Category;
use Gally\Product\Model\Product;
use Gally\Search\GraphQl\Type\Definition\FieldFilterInputType;
use Gally\Search\GraphQl\Type\Definition\SortInputType;
use Gally\Search\Resolver\DummyResolver;

#[
    ApiResource(
        collectionOperations: [],
        graphql: [
            'search' => [
                'collection_query' => DummyResolver::class,
                'pagination_type' => 'page',
                'args' => [
                    'entityType' => ['type' => 'String!', 'description' => 'Entity Type'],
                    'localizedCatalog' => ['type' => 'String!', 'description' => 'Localized Catalog'],
                    'search' => ['type' => 'String', 'description' => 'Query Text'],
                    'currentPage' => ['type' => 'Int'],
                    'pageSize' => ['type' => 'Int'],
                    'sort' => ['type' => SortInputType::NAME],
                    'filter' => ['type' => '[' . FieldFilterInputType::NAME . ']', ReadStage::IS_GRAPHQL_GALLY_ARG_KEY => true],
                ],
                'read' => true, // Required so the dataprovider is called.
                'deserialize' => true,
                'write' => false,
                'serialize' => true,
            ],
        ],
        itemOperations: [
            'get' => [
                'controller' => NotFoundAction::class,
                'read' => false,
                'output' => false,
            ],
        ],
        paginationClientEnabled: true,
        paginationClientItemsPerPage: true,
        paginationClientPartial: false,
        paginationEnabled: true,
        paginationItemsPerPage: 30, // Default items per page if pageSize not provided.
        paginationMaximumItemsPerPage: 100, // Max. allowed items per page.
    ),
]
class Autocomplete
{
    private string $id;

    /** @var array<Product|Category> */
    private array $documents;

    #[ApiProperty(identifier: true)]
    public function getId(): string
    {
        // We need and id field different that the value field because authorized characters in the id field are limited
        // Api platform use this field to build entity URI.
        return $this->id;
    }

    public function getDocuments(): array
    {
        return $this->documents;
    }
}
