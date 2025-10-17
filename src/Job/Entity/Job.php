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

namespace Gally\Job\Entity;

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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Gally\Job\Entity\Job\ImportFile;
use Gally\Job\Entity\Job\Log;
use Gally\User\Constant\Role;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new Get(security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
        new Delete(security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
        new GetCollection(security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
        new Post(security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
    ],
    graphQlOperations: [
        new Query(name: 'item_query', security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
        new QueryCollection(name: 'collection_query', security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
        new Mutation(name: 'create', security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
        new Mutation(name: 'delete', security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
    ],
    denormalizationContext: ['groups' => ['job:write']],
    normalizationContext: ['groups' => ['job:read']],
)]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['type' => 'exact', 'profile' => 'exact', 'status' => 'exact'])]
class Job
{
    use TimestampableEntity;
    public const STATUS_NEW = 'new';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_FINISHED = 'finished';
    public const STATUS_FAILED = 'error';

    public const TYPE_EXPORT = 'export';
    public const TYPE_IMPORT = 'import';

    #[Groups(['job:read', 'job:write'])]
    private ?int $id = null;

    #[ApiProperty(
        extraProperties: [
            'hydra:supportedProperty' => [
                'hydra:property' => [
                    'rdfs:label' => 'Type',
                ],
                'gally' => [
                    'visible' => true,
                    'editable' => false,
                    'position' => 10,
                ],
            ],
        ],
    )]
    #[Groups(['job:read', 'job:write'])]
    private string $type;

    #[ApiProperty(
        extraProperties: [
            'hydra:supportedProperty' => [
                'hydra:property' => [
                    'rdfs:label' => 'Profile',
                ],
                'gally' => [
                    'visible' => true,
                    'editable' => false,
                    'position' => 20,
                ],
            ],
        ],
    )]
    #[Groups(['job:read', 'job:write'])]
    private string $profile;

    #[ApiProperty(
        extraProperties: [
            'hydra:supportedProperty' => [
                'hydra:property' => [
                    'rdfs:label' => 'Status',
                ],
                'gally' => [
                    'visible' => true,
                    'editable' => false,
                    'position' => 30,
                ],
            ],
        ],
    )]
    #[Groups(['job:read'])]
    private string $status = self::STATUS_NEW;

    #[Groups(['job:read', 'job:write'])]
    private ImportFile $importFile;

    /** @var \Doctrine\Common\Collections\Collection&iterable<Log> */
    #[Groups(['job:read'])]
    private Collection $logs;

    #[Groups(['job:read'])]
    protected $createdAt;

    #[Groups(['boost:read'])]
    protected $updatedAt;

    #[Groups(['job:read'])]
    protected ?\DateTime $finishedAt;

    public function __construct()
    {
        $this->logs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getProfile(): string
    {
        return $this->profile;
    }

    public function setProfile(string $profile): self
    {
        $this->profile = $profile;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getLogs(): Collection
    {
        return $this->logs;
    }

    public function setLogs(Collection $logs): self
    {
        $this->logs = $logs;

        return $this;
    }

    public function getImportFile(): ImportFile
    {
        return $this->importFile;
    }

    public function setImportFile(ImportFile $importFile): self
    {
        $this->importFile = $importFile;

        return $this;
    }

    public function getFinishedAt(): ?\DateTime
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?\DateTime $finishedAt): void
    {
        $this->finishedAt = $finishedAt;
    }
}
