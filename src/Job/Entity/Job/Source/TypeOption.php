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

namespace Gally\Job\Entity\Job\Source;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use Gally\Job\State\Source\TypeOptionProvider;
use Gally\User\Constant\Role;

#[ApiResource(
    operations: [
        new GetCollection(
            security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')",
            paginationEnabled: false
        ),
    ],
    graphQlOperations: [
        new QueryCollection(
            name: 'collection_query',
            security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')",
            paginationEnabled: false
        ),
    ],
    provider: TypeOptionProvider::class,
    shortName: 'JobTypeOption',
)]
class TypeOption
{
    #[ApiProperty(identifier: true)]
    public string $value;

    public string $label;
}
