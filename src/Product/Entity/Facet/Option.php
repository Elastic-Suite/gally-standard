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

namespace Gally\Product\Entity\Facet;

use ApiPlatform\Action\NotFoundAction;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use Gally\Product\State\Facet\OptionProvider;
use Gally\Search\Entity\Facet\Option as FacetOption;
use Gally\Search\Resolver\DummyResolver;

#[ApiResource(
    operations: [
        new Get(controller: NotFoundAction::class, read: false, output: false),
    ],
    graphQlOperations: [
        new QueryCollection(
            name: 'viewMore',
            resolver: DummyResolver::class,
            read: true,
            deserialize: false,
            args: [
                'localizedCatalog' => [
                    'type' => 'String!',
                    'description' => 'Localized Catalog',
                ],
                'aggregation' => [
                    'type' => 'String!',
                    'description' => 'Source field to get complete aggregation',
                ],
                'search' => [
                    'type' => 'String',
                    'description' => 'Query Text',
                ],
                'currentCategoryId' => [
                    'type' => 'String',
                    'description' => 'Current category ID',
                ],
                'filter' => [
                    'type' => '[ProductFieldFilterInput]',
                    'is_gally_arg' => true,
                ],
            ]
        ),
    ],
    provider: OptionProvider::class,
    shortName: 'ProductFacetOption',
    paginationEnabled: false
)]
class Option extends FacetOption
{
}
