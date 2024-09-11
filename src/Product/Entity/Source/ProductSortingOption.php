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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use Gally\Metadata\Entity\SourceField;
use Gally\Product\State\ProductSortingOptionProvider;
use Gally\Search\Entity\Source\SortingOption;

#[ApiResource(
    operations: [
        new GetCollection(paginationEnabled: false),
    ],
    graphQlOperations: [
        new QueryCollection(name: 'collection_query', paginationEnabled: false),
    ],
    provider: ProductSortingOptionProvider::class,
    extraProperties: [
        'gally' => ['cache_tag' => [
            'resource_classes' => [SourceField::class],
        ],
        ],
    ]
)]
class ProductSortingOption extends SortingOption
{
}
