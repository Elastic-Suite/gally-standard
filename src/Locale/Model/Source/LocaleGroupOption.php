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

namespace Gally\Locale\Model\Source;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Gally\Catalog\Model\LocalizedCatalog;
use Gally\User\Constant\Role;

#[ApiResource(
    itemOperations: [],
    collectionOperations: [
        'get' => [
            'pagination_enabled' => false,
            'security' => "is_granted('" . Role::ROLE_CONTRIBUTOR . "')",
        ],
    ],
    graphql: [
        'collection_query' => [
            'pagination_enabled' => false,
            'security' => "is_granted('" . Role::ROLE_CONTRIBUTOR . "')",
        ],
    ],
    attributes: [
        'gally' => [
            // Allows to add cache tag "/localized_catalogs" in the HTTP response to invalidate proxy cache when a source field is saved.
            'cache_tag' => ['resource_classes' => [LocalizedCatalog::class]],
        ],
    ],
)]
class LocaleGroupOption
{
    #[ApiProperty(identifier: true)]
    public string $value;

    public string $label;

    public array $options;
}
