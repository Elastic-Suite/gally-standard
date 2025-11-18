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
use Gally\Catalog\Entity\LocalizedCatalog;
use Gally\Configuration\State\Source\LanguageOptionProvider;
use Gally\Locale\Entity\Source\LanguageOption as BaseLanguageOption;
use Gally\User\Constant\Role;

#[ApiResource(
    operations: [
        new GetCollection(paginationEnabled: false, security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
    ],
    graphQlOperations: [
        new QueryCollection(name: 'collection_query', paginationEnabled: false, security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
    ],
    extraProperties: [
        'gally' => [
            'cache_tag' => ['resource_classes' => [LocalizedCatalog::class]],
        ],
    ],
    provider: LanguageOptionProvider::class,
    shortName: 'ConfigurationLanguageOption'
)]
class LanguageOption extends BaseLanguageOption
{
}
