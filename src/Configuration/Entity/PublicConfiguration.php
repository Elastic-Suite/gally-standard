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

namespace Gally\Configuration\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use Gally\Configuration\State\PublicConfigurationProvider;

#[ApiResource(
    operations: [
        new GetCollection(),
    ],
    graphQlOperations: [
        new QueryCollection(normalizationContext: ['groups' => ['configuration:graphql']]),
    ],
    paginationType: 'page',
    provider: PublicConfigurationProvider::class,
    normalizationContext: ['groups' => ['configuration:read']],
)]
class PublicConfiguration extends Configuration
{
}
