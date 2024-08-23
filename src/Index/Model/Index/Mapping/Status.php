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

namespace Gally\Index\Model\Index\Mapping;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GraphQl\Query;
use Gally\Index\State\MappingStatusProvider;
use Gally\User\Constant\Role;

#[ApiResource(
    operations: [
        new Get(security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
    ],
    graphQlOperations: [
        new Query(
            name: 'get',
            resolver: MappingStatusProvider::class,
            args: ['entityType' => ['type' => 'String!']],
            security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"
        ),
    ],
    provider: MappingStatusProvider::class,
    shortName: 'MappingStatus'
)]
class Status
{
    public const Green = 'green';     // Current index mapping is accurate with metadata
    public const Yellow = 'yellow';   // Current index mapping is not accurate, mapping will be taken into account on next reindex
    public const Red = 'red';         // Current index metadata is not enough qualified

    #[ApiProperty(identifier: true)]
    public string $entityType;

    public string $status;

    public function __construct(string $entityType, string $status)
    {
        $this->entityType = $entityType;
        $this->status = $status;
    }
}
