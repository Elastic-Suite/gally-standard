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

namespace Gally\Job\Entity\Job;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GraphQl\Query;
use Gally\Job\Controller\JobProfilesController;
use Gally\Job\Resolver\JobProfilesResolver;
use Gally\User\Constant\Role;

#[ApiResource(
    operations: [
        new Get(
            security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')",
            uriTemplate: 'job_profiles',
            read: false,
            deserialize: false,
            serialize: true,
            controller: JobProfilesController::class
        ),
    ],
    graphQlOperations: [
        new Query(
            name: 'get',
            resolver: JobProfilesResolver::class,
            read: false,
            deserialize: false,
            args: [],
            security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"
        ),
    ],
    shortName: 'JobProfiles',
    paginationEnabled: false
)]

class Profiles
{
    private string $id = 'job_profiles';

    private array $profiles = [];

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getProfiles(): ?array
    {
        return $this->profiles;
    }

    public function setProfiles(array $profiles): self
    {
        $this->profiles = $profiles;

        return $this;
    }
}
