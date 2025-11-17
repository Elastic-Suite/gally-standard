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

namespace Gally\Configuration\Entity\Source;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use Gally\Catalog\Entity\Catalog;
use Gally\Catalog\Entity\LocalizedCatalog;
use Gally\Catalog\Entity\Source\LocalizedCatalogGroupOption as BaseLocalizedCatalogGroupOption;
use Gally\Configuration\State\Source\LocalizedCatalogGroupOptionProvider;

#[ApiResource(
    operations: [
        new GetCollection(
            paginationEnabled: false,
            openapiContext: [
                'parameters' => [
                    ['name' => 'keyToGetOnValue', 'in' => 'query', 'type' => 'string'],
                ],
            ],
        ),
    ],
    graphQlOperations: [new QueryCollection(
        name: 'collection_query',
        paginationEnabled: false,
        args: [
            'keyToGetOnValue' => ['type' => 'String'],
        ],
    )],
    provider: LocalizedCatalogGroupOptionProvider::class,
    extraProperties: [
        'gally' => ['cache_tag' => ['resource_classes' => [Catalog::class, LocalizedCatalog::class]]],
    ],
    shortName: 'ConfigurationLocalizedCatalogGroupOption'
)]
class LocalizedCatalogGroupOption extends BaseLocalizedCatalogGroupOption
{
}
