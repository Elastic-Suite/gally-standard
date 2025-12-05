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
use Gally\Job\Controller\DownloadJobFile;
use Gally\Job\Entity\Job\File;
use Gally\Job\Entity\Job\Log;
use Gally\User\Constant\Role;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new Get(security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
        new Get(
            security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"
        ),

        new Get(
            uriTemplate: '/jobs/{id}/download',
            controller: DownloadJobFile::class,
            security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')",
            read: true,
            output: false,
            openapiContext: [
                'summary' => 'Download job file',
                'responses' => [
                    '200' => [
                        'description' => 'File downloaded',
                        'content' => [
                            'text/csv' => ['schema' => ['type' => 'string', 'format' => 'binary']],
                        ],
                    ],
                ],
            ]
        ),
        new Delete(security: "is_granted('" . Role::ROLE_ADMIN . "')"),
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

    public const STATUSES = [
        self::STATUS_NEW,
        self::STATUS_PROCESSING,
        self::STATUS_FINISHED,
        self::STATUS_FAILED,
    ];

    public const STATUS_OPTIONS = [
        ['label' => 'job.status.new.label', 'value' => self::STATUS_NEW],
        ['label' => 'job.status.processing.label', 'value' => self::STATUS_PROCESSING],
        ['label' => 'job.status.finished.label', 'value' => self::STATUS_FINISHED],
        ['label' => 'job.status.failed.label', 'value' => self::STATUS_FAILED],
    ];

    public const TYPE_EXPORT = 'export';
    public const TYPE_IMPORT = 'import';

    public const TYPES = [
        self::TYPE_EXPORT,
        self::TYPE_IMPORT,
    ];

    public const TYPE_OPTIONS = [
        ['label' => 'job.type.export.label', 'value' => self::TYPE_EXPORT],
        ['label' => 'job.type.import.label', 'value' => self::TYPE_IMPORT],
    ];

    #[Groups(['job:read', 'job:write'])]
    private ?int $id = null;

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
                    'input' => 'select',
                    'options' => [
                        'api_rest' => '/job_profile_options',
                        'api_graphql' => 'jobProfileOptions',
                    ],
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
                    'input' => 'select',
                    'options' => [
                        'api_rest' => '/job_status_options',
                        'api_graphql' => 'jobStatusOptions',
                    ],
                ],
            ],
        ],
    )]
    #[Groups(['job:read'])]
    private string $status = self::STATUS_NEW;

    #[Groups(['job:read', 'job:write'])]
    private ?File $file = null;

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

    #[ApiProperty(
        extraProperties: [
            'hydra:supportedProperty' => [
                'hydra:property' => [
                    'rdfs:label' => 'Logs',
                ],
                'gally' => [
                    'visible' => true,
                    'editable' => false,
                    'position' => 70,
                    'input' => 'logs',
                    'options' => [
                        'api_rest' => '/job_logs',
                        'api_graphql' => 'jobLog',
                    ],
                ],
            ],
        ],
    )]
    public function getLogs(): Collection
    {
        return $this->logs;
    }

    public function setLogs(Collection $logs): self
    {
        $this->logs = $logs;

        return $this;
    }

    #[ApiProperty(
        extraProperties: [
            'hydra:supportedProperty' => [
                'hydra:property' => [
                    'rdfs:label' => 'File',
                ],
                'gally' => [
                    'visible' => true,
                    'editable' => false,
                    'position' => 60,
                    'input' => 'jobfile',
                    'options' => [
                        'api_rest' => '/job_files',
                        'api_graphql' => 'jobFile',
                    ],
                ],
            ],
        ],
    )]
    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFile(File $file): self
    {
        $this->file = $file;

        return $this;
    }

    #[ApiProperty(
        extraProperties: [
            'hydra:supportedProperty' => [
                'hydra:property' => [
                    'rdfs:label' => 'Created at',
                ],
                'gally' => [
                    'visible' => true,
                    'editable' => false,
                    'position' => 40,
                    'input' => 'date',
                ],
            ],
        ],
    )]
    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    #[ApiProperty(
        extraProperties: [
            'hydra:supportedProperty' => [
                'hydra:property' => [
                    'rdfs:label' => 'Finished at',
                ],
                'gally' => [
                    'visible' => true,
                    'editable' => false,
                    'position' => 50,
                    'input' => 'date',
                ],
            ],
        ],
    )]
    public function getFinishedAt(): ?\DateTime
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?\DateTime $finishedAt): self
    {
        $this->finishedAt = $finishedAt;

        return $this;
    }

    public static function getStatuses(): array
    {
        return self::STATUSES;
    }

    public static function getTypes(): array
    {
        return self::TYPES;
    }
}
