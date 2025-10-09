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
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use Gally\Job\Entity\Job;
use Gally\User\Constant\Role;
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
    private ?string $message;

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

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

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

    public static function getSeverities(): array
    {
        return ['INFO', 'WARNING', 'ERROR', 'CRITICAL'];
    }
}
