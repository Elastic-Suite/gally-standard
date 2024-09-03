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

namespace Gally\Index\Model;

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
use Gally\Catalog\Model\LocalizedCatalog;
use Gally\Index\Dto\CreateIndexDto;
use Gally\Index\Dto\InstallIndexDto;
use Gally\Index\Dto\RefreshIndexDto;
use Gally\Index\MutationResolver\BulkDeleteIndexMutation;
use Gally\Index\MutationResolver\BulkIndexMutation;
use Gally\Index\MutationResolver\CreateIndexMutation;
use Gally\Index\MutationResolver\InstallIndexMutation;
use Gally\Index\MutationResolver\RefreshIndexMutation;
use Gally\Index\State\CreateIndexProcessor;
use Gally\Index\State\IndexProcessor;
use Gally\Index\State\IndexProvider;
use Gally\Index\State\InstallIndexProcessor;
use Gally\Index\State\RefreshIndexProcessor;
use Gally\User\Constant\Role;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new Get(
            normalizationContext: ['groups' => ['details']],
            denormalizationContext: ['groups' => ['details']],
            security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"
        ),
        new Delete(
            security: "is_granted('" . Role::ROLE_ADMIN . "')"
        ),
        new Put(
            openapiContext: [
                'description' => 'Installs an Index resource',
                'summary' => 'Installs an Index resource',
            ],
            serialize: false,
            uriTemplate: '/indices/install/{name}',
            input: InstallIndexDto::class,
            processor: InstallIndexProcessor::class,
            security: "is_granted('" . Role::ROLE_ADMIN . "')",
        ),
        new Put(
            openapiContext: [
                'description' => 'Refreshes an Index resource',
                'summary' => 'Refreshes an Index resource',
            ],
            uriTemplate: '/indices/refresh/{name}',
            serialize: false,
            input: RefreshIndexDto::class,
            processor: RefreshIndexProcessor::class,
            security: "is_granted('" . Role::ROLE_ADMIN . "')",
        ),
        new GetCollection(
            security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')",
        ),
        new Post(
            input: CreateIndexDto::class,
            processor: CreateIndexProcessor::class,
            write: true,
            serialize: true,
            security: "is_granted('" . Role::ROLE_ADMIN . "')",
            normalizationContext: ['groups' => ['create']],
            denormalizationContext: ['groups' => ['create']],
        ),
    ],
    graphQlOperations: [
        new Query(
            name: 'item_query',
            normalizationContext: ['groups' => ['details']],
            denormalizationContext: ['groups' => ['details']],
            security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')",
        ),
        new QueryCollection(
            name: 'collection_query',
            security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')",
        ),
        new Mutation(
            name: 'create',
            resolver: CreateIndexMutation::class,
            args: [
                'entityType' => [
                    'type' => 'String!',
                    'description' => 'Entity type for which to create an index',
                ],
                'localizedCatalog' => [
                    'type' => 'String!',
                    'description' => 'Catalog scope for which to create an index',
                ],
            ],
            read: false,
            deserialize: false,
            write: false,
            serialize: true,
            security: "is_granted('" . Role::ROLE_ADMIN . "')",
            normalizationContext: ['groups' => ['details']],
            denormalizationContext: ['groups' => ['details']]),
        new Mutation(
            name: 'update',
            security: "is_granted('" . Role::ROLE_ADMIN . "')",
        ),
        new Mutation(
            name: 'delete',
            security: "is_granted('" . Role::ROLE_ADMIN . "')",
        ),
        new Mutation(
            name: 'bulk',
            resolver: BulkIndexMutation::class,
            args: [
                'indexName' => ['type' => 'String!'],
                'data' => ['type' => 'String!'],
            ],
            read: false,
            deserialize: false,
            write: false,
            serialize: true,
            security: "is_granted('" . Role::ROLE_ADMIN . "')",
        ),
        new Mutation(
            name: 'bulkDelete',
            resolver: BulkDeleteIndexMutation::class,
            args: [
                'indexName' => ['type' => 'String!'],
                'ids' => ['type' => '[ID]!'],
            ],
            read: false,
            deserialize: false,
            write: false,
            serialize: true,
            security: "is_granted('" . Role::ROLE_ADMIN . "')",
        ),
        new Mutation(
            name: 'install',
            resolver: InstallIndexMutation::class,
            args: [
                'name' => [
                    'type' => 'String!',
                    'description' => 'Full index name',
                ],
            ],
            read: true,
            deserialize: true,
            write: false,
            serialize: true,
            security: "is_granted('" . Role::ROLE_ADMIN . "')",
        ),
        new Mutation(
            name: 'refresh',
            resolver: RefreshIndexMutation::class,
            args: [
                'name' => [
                    'type' => 'String!',
                    'description' => 'Full index name',
                ],
            ],
            read: true,
            deserialize: true,
            write: false,
            serialize: true,
            security: "is_granted('" . Role::ROLE_ADMIN . "')",
        ),
    ],
    provider: IndexProvider::class,
    processor: IndexProcessor::class,
    denormalizationContext: ['groups' => ['list']],
    normalizationContext: ['groups' => ['list']],
    paginationEnabled: false,
)]
class Index
{
    public const STATUS_LIVE = 'live';
    public const STATUS_EXTERNAL = 'external';
    public const STATUS_GHOST = 'ghost';
    public const STATUS_INVALID = 'invalid';
    public const STATUS_INDEXING = 'indexing';

    #[ApiProperty(identifier: true)]
    #[Groups(['list', 'details', 'create'])]
    private string $name;

    /** @var string[] */
    #[Groups(['list', 'details', 'create'])]
    private array $aliases;

    #[Groups(['list', 'details'])]
    private int $docsCount;

    #[Groups(['list', 'details'])]
    private string $size;

    #[Groups(['list', 'details'])]
    private ?string $entityType;

    #[Groups(['list', 'details'])]
    private ?LocalizedCatalog $localizedCatalog;

    #[Groups(['list', 'details'])]
    private string $status;

    #[Groups(['details'])]
    private array $mapping;

    #[Groups(['details'])]
    private array $settings;

    /**
     * @param string   $name      Index name
     * @param string[] $aliases   Index aliases
     * @param int      $docsCount Index documents count
     * @param string   $size      Index size
     */
    public function __construct(
        string $name,
        array $aliases = [],
        int $docsCount = 0,
        string $size = '',
    ) {
        $this->name = $name;
        $this->aliases = $aliases;
        $this->docsCount = $docsCount;
        $this->size = $size;
        $this->entityType = null;
        $this->localizedCatalog = null;
        $this->status = self::STATUS_EXTERNAL;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string[]
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * @param string[] $aliases index aliases
     */
    public function setAliases(array $aliases): void
    {
        $this->aliases = $aliases;
    }

    public function getDocsCount(): int
    {
        return $this->docsCount;
    }

    public function getSize(): string
    {
        return $this->size;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getLocalizedCatalog(): ?LocalizedCatalog
    {
        return $this->localizedCatalog;
    }

    public function setLocalizedCatalog(?LocalizedCatalog $localizedCatalog): void
    {
        $this->localizedCatalog = $localizedCatalog;
    }

    public function getEntityType(): ?string
    {
        return $this->entityType;
    }

    public function setEntityType(?string $entityType): void
    {
        $this->entityType = $entityType;
    }

    public function getMapping(): array
    {
        return $this->mapping;
    }

    public function setMapping(array $mapping): void
    {
        $this->mapping = $mapping;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function setSettings(array $settings): void
    {
        $this->settings = $settings;
    }
}
