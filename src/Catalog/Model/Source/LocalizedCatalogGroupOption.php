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

namespace Gally\Catalog\Model\Source;

use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Gally\Catalog\Model\Catalog;
use Gally\Catalog\Model\LocalizedCatalog;
use Gally\Catalog\State\Source\LocalizedCatalogGroupOptionProvider;

#[ApiResource(
    operations: [new GetCollection(paginationEnabled: false)],
    graphQlOperations: [new QueryCollection(name: 'collection_query', paginationEnabled: false)],
    provider: LocalizedCatalogGroupOptionProvider::class,
    extraProperties: [
        'gally' => ['cache_tag' => ['resource_classes' => [Catalog::class, LocalizedCatalog::class]]]
    ]

)]
class LocalizedCatalogGroupOption
{
    #[ApiProperty(identifier: true)]
    public string $value;

    public string $label;

    public array $options;
}
