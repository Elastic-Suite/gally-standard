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

namespace Gally\Configuration\Model;

use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use Gally\Configuration\State\ConfigurationProvider;

#[ApiResource(
    operations: [new GetCollection(paginationEnabled: false)],
    graphQlOperations: [new QueryCollection(name: 'collection_query', paginationEnabled: false)],
    provider: ConfigurationProvider::class,
)]

class Configuration
{
    #[ApiProperty(identifier: true)]
    public string $id;

    public string $value;
}
