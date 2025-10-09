<?php
/**
 * DISCLAIMER.
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Licensed to Smile-SA. All rights reserved. No warranty, explicit or implicit, provided.
 *            Unauthorized copying of this file, via any medium, is strictly prohibited.
 */

declare(strict_types=1);

namespace Gally\Job\Entity\Job;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Gally\Boost\Entity\Boost\CategoryLimitation;
use Gally\Boost\Entity\Boost\RequestType;
use Gally\Boost\Entity\Boost\SearchLimitation;
use Gally\Boost\State\BoostProcessor;
use Gally\Boost\State\BoostProvider;
use Gally\Catalog\Entity\LocalizedCatalog;
use Gally\Job\Entity\Job;
use Gally\User\Constant\Role;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new Get(security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
        new GetCollection(security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
    ],
    graphQlOperations: [
        new Query(name: 'item_query', security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
        new QueryCollection(name: 'collection_query', security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
    ],
    denormalizationContext: ['groups' => ['job:write']],
    normalizationContext: ['groups' => ['job:read']],
    shortName: 'JobLog'
)]
class Log
{
    #[Groups(['job:read'])]
    private ?int $id = null;

    private Job $job;

    #[Groups(['job:read'])]
    private string $severity;

    #[Groups(['job:read'])]
    private string $log;

    #[Groups(['job:read'])]
    private \DateTime $loggedAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJob(): Job
    {
        return $this->job;
    }

    public function setJob(Job $job): self
    {
        $this->job = $job;

        return $this;
    }

    public function getSeverity(): string
    {
        return $this->severity;
    }

    public function setSeverity(string $severity): self
    {
        $this->severity = $severity;

        return $this;
    }

    public function getLog(): string
    {
        return $this->log;
    }

    public function setLog(string $log): self
    {
        $this->log = $log;

        return $this;
    }

    public function getLoggedAt(): \DateTime
    {
        return $this->loggedAt;
    }

    public function setLoggedAt(\DateTime $loggedAt): self
    {
        $this->loggedAt = $loggedAt;

        return $this;
    }
}
