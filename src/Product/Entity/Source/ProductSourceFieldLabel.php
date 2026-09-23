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

namespace Gally\Product\Entity\Source;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\OpenApi\Model;
use Gally\Metadata\Entity\SourceField;
use Gally\Product\State\ProductSourceFieldLabelProvider;

/**
 * Translated label of a product source field, for a given localized catalog.
 *
 * The caller must name the codes it wants: there is no operation listing every source field,
 * and an unknown code is answered like a real source field without a label, so the response
 * cannot be used to discover which codes exist.
 */
#[ApiResource(
    operations: [
        new GetCollection(
            paginationEnabled: false,
            openapi: new Model\Operation(
                parameters: [
                    new Model\Parameter(
                        name: 'codes[]',
                        in: 'query',
                        required: true,
                        schema: ['type' => 'array', 'items' => ['type' => 'string']],
                    ),
                    new Model\Parameter(
                        name: 'localizedCatalog',
                        in: 'query',
                        required: true,
                        schema: ['type' => 'string'],
                    ),
                ],
            )
        ),
    ],
    graphQlOperations: [
        new QueryCollection(
            name: 'collection_query',
            paginationEnabled: false,
            args: [
                'codes' => ['type' => '[String!]!'],
                'localizedCatalog' => ['type' => 'String!'],
            ]
        ),
    ],
    provider: ProductSourceFieldLabelProvider::class,
    extraProperties: [
        'gally' => ['cache_tag' => [
            'resource_classes' => [SourceField::class],
        ],
        ],
    ]
)]
class ProductSourceFieldLabel
{
    #[ApiProperty(identifier: true)]
    public string $code;

    public string $label;
}
