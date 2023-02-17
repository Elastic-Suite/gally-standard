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

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Gally\Catalog\Model\Catalog;
use Gally\Catalog\Model\LocalizedCatalog;

#[ApiResource(
    itemOperations: [],
    collectionOperations: [
        'get' => ['pagination_enabled' => false],
    ],
    graphql: [
        'collection_query' => ['pagination_enabled' => false],
    ],
    attributes: [
        'gally' => [
            // Allows to add cache tag "/source_fields" in the HTTP response to invalidate proxy cache when a source field is saved.
            'cache_tag' => ['resource_classes' => [Catalog::class, LocalizedCatalog::class]],
        ],
    ],
)]
class LocalizedCatalogGroupOption
{
    #[ApiProperty(identifier: true)]
    public string $value;

    public string $label;

    public array $options;
}
