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

namespace Gally\Index\Model;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Gally\Catalog\Model\LocalizedCatalog;
use Gally\Index\Dto\CreateIndexInput;
use Gally\Index\Dto\InstallIndexInput;
use Gally\Index\Dto\RefreshIndexInput;
use Gally\Index\MutationResolver\BulkDeleteIndexMutation;
use Gally\Index\MutationResolver\BulkIndexMutation;
use Gally\Index\MutationResolver\CreateIndexMutation;
use Gally\Index\MutationResolver\InstallIndexMutation;
use Gally\Index\MutationResolver\RefreshIndexMutation;
use Gally\User\Constant\Role;
use Symfony\Component\Serializer\Annotation\Groups;

#[
    ApiResource(
        collectionOperations: [
            'get' => ['security' => "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"],
            'post' => [
                'method' => 'POST',
                'input' => CreateIndexInput::class,
                'write' => false,
                'serialize' => true,
                'security' => "is_granted('" . Role::ROLE_ADMIN . "')",
                'normalization_context' => ['groups' => ['create']],
                'denormalization_context' => ['groups' => ['create']],
            ],
        ],
        graphql: [
            // Auto-generated queries and mutations.
            'item_query' => [
                'normalization_context' => ['groups' => ['details']],
                'denormalization_context' => ['groups' => ['details']],
                'security' => "is_granted('" . Role::ROLE_CONTRIBUTOR . "')",
            ],
            'collection_query' => ['security' => "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"],
            'create' => [
                'mutation' => CreateIndexMutation::class,
                'args' => [
                    'entityType' => ['type' => 'String!', 'description' => 'Entity type for which to create an index'],
                    'localizedCatalog' => ['type' => 'String!', 'description' => 'Catalog scope for which to create an index'],
                ],
                'read' => false,
                'deserialize' => false,
                'write' => false,
                'serialize' => true,
                'security' => "is_granted('" . Role::ROLE_ADMIN . "')",
                'normalization_context' => ['groups' => ['details']],
                'denormalization_context' => ['groups' => ['details']],
            ],
            'update' => ['security' => "is_granted('" . Role::ROLE_ADMIN . "')"],
            'delete' => ['security' => "is_granted('" . Role::ROLE_ADMIN . "')"],
            'bulk' => [
                'mutation' => BulkIndexMutation::class,
                'args' => [
                    'indexName' => ['type' => 'String!'],
                    'data' => ['type' => 'String!'],
                ],
                'read' => false,
                'deserialize' => false,
                'write' => false,
                'serialize' => true,
                'security' => "is_granted('" . Role::ROLE_ADMIN . "')",
            ],
            'bulkDelete' => [
                'mutation' => BulkDeleteIndexMutation::class,
                'args' => [
                    'indexName' => ['type' => 'String!'],
                    'ids' => ['type' => '[ID]!'],
                ],
                'read' => false,
                'deserialize' => false,
                'write' => false,
                'serialize' => true,
                'security' => "is_granted('" . Role::ROLE_ADMIN . "')",
            ],
            'install' => [
                'mutation' => InstallIndexMutation::class,
                'args' => [
                    'name' => ['type' => 'String!', 'description' => 'Full index name'],
                ],
                'read' => true,
                'deserialize' => true,
                'write' => false,
                'serialize' => true,
                'security' => "is_granted('" . Role::ROLE_ADMIN . "')",
            ],
            'refresh' => [
                'mutation' => RefreshIndexMutation::class,
                'args' => [
                    'name' => ['type' => 'String!', 'description' => 'Full index name'],
                ],
                'read' => true,
                'deserialize' => true,
                'write' => false,
                'serialize' => true,
                'security' => "is_granted('" . Role::ROLE_ADMIN . "')",
            ],
        ],
        itemOperations: [
            'get' => [
                'normalization_context' => ['groups' => ['details']],
                'denormalization_context' => ['groups' => ['details']],
                'security' => "is_granted('" . Role::ROLE_CONTRIBUTOR . "')",
            ],
            'delete' => ['security' => "is_granted('" . Role::ROLE_ADMIN . "')"],
            'install' => [
                'openapi_context' => [
                    'description' => 'Installs an Index resource',
                    'summary' => 'Installs an Index resource',
                ],
                'path' => '/indices/install/{name}',
                'method' => 'PUT',
                'input' => InstallIndexInput::class, // RefreshIndexInput::class,
                'deserialize' => true,
                'read' => true,
                'write' => false,
                'serialize' => true,
                'security' => "is_granted('" . Role::ROLE_ADMIN . "')",
            ],
            'refresh' => [
                'openapi_context' => [
                    'description' => 'Refreshes an Index resource',
                    'summary' => 'Refreshes an Index resource',
                ],
                'path' => '/indices/refresh/{name}',
                'method' => 'PUT',
                'input' => RefreshIndexInput::class,
                'deserialize' => true,
                'read' => true,
                'write' => false,
                'serialize' => true,
                'security' => "is_granted('" . Role::ROLE_ADMIN . "')",
            ],
        ],
        normalizationContext: ['groups' => ['list']],
        denormalizationContext: ['groups' => ['list']],
        paginationEnabled: false,
    ),
]
class Index
{
    public const STATUS_LIVE = 'live';
    public const STATUS_EXTERNAL = 'external';
    public const STATUS_GHOST = 'ghost';
    public const STATUS_INVALID = 'invalid';
    public const STATUS_INDEXING = 'indexing';

    #[ApiProperty(
        identifier: true
    )]
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
