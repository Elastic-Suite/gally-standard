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

namespace Gally\Index\Entity;

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
use Gally\Catalog\Entity\LocalizedCatalog;
use Gally\Index\Dto\DataStreamInput;
use Gally\Index\MutationResolver\CreateDataStreamMutation;
use Gally\Index\State\CreateDataStreamProcessor;
use Gally\Index\State\DataStreamProcessor;
use Gally\Index\State\DataStreamProvider;
use Gally\User\Constant\Role;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new Post(
            input: DataStreamInput::class,
            processor: CreateDataStreamProcessor::class,
            write: true,
            serialize: true,
            security: "is_granted('" . Role::ROLE_ADMIN . "')",
            normalizationContext: ['groups' => ['create']],
            denormalizationContext: ['groups' => ['create']],
        ),
        new Get(
            normalizationContext: ['groups' => ['details']],
            denormalizationContext: ['groups' => ['details']],
            security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"
        ),
        new GetCollection(
            security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')",
        ),
        new Delete(
            security: "is_granted('" . Role::ROLE_ADMIN . "')"
        ),
    ],
    graphQlOperations: [
        new Mutation(
            name: 'create',
            resolver: CreateDataStreamMutation::class,
            args: [
                'entityType' => [
                    'type' => 'String!',
                    'description' => 'Entity type for which to create a data stream',
                ],
                'localizedCatalog' => [
                    'type' => 'String!',
                    'description' => 'Catalog scope for which to create a data stream',
                ],
            ],
            read: false,
            deserialize: false,
            write: false,
            serialize: true,
            security: "is_granted('" . Role::ROLE_ADMIN . "')",
            normalizationContext: ['groups' => ['details']],
            denormalizationContext: ['groups' => ['details']],
        ),
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
            name: 'bulk',
            args: [
                'name' => ['type' => 'String!'],
                'data' => ['type' => 'String!'],
            ],
            security: "is_granted('" . Role::ROLE_ADMIN . "')",
        ),
        new Mutation(
            name: 'delete',
            security: "is_granted('" . Role::ROLE_ADMIN . "')",
        ),
    ],
    provider: DataStreamProvider::class,
    processor: DataStreamProcessor::class,
    denormalizationContext: ['groups' => ['list']],
    normalizationContext: ['groups' => ['list']],
    paginationEnabled: false,
)]
class DataStream
{
    #[ApiProperty(identifier: true)]
    #[Groups(['list', 'details', 'create'])]
    private string $name;

    /** @var Collection&iterable<Index> */
    private Collection $indices; // Not in serialization context because api platform is not able to get non orm entities.

    #[Groups(['list', 'details'])]
    private string $status;

    #[Groups(['list', 'details'])]
    private ?IndexTemplate $template;

    #[Groups(['list', 'details'])]
    private ?string $entityType;

    #[Groups(['list', 'details'])]
    private ?LocalizedCatalog $localizedCatalog;

    public function __construct(
        string $name,
        string $status = 'active',
        ?IndexTemplate $template = null,
        ?string $entityType = null,
        ?LocalizedCatalog $localizedCatalog = null,
    ) {
        $this->name = $name;
        $this->status = $status;
        $this->template = $template;
        $this->entityType = $entityType;
        $this->localizedCatalog = $localizedCatalog;
        $this->indices = new ArrayCollection();
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
     * @return Index[]
     */
    public function getIndices(): array
    {
        return $this->indices->toArray();
    }

    public function addIndex(Index $index): self
    {
        if (!$this->indices->contains($index)) {
            $this->indices[] = $index;
        }

        return $this;
    }

    public function removeIndex(Index $index): self
    {
        $this->indices->removeElement($index);

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getTemplate(): ?IndexTemplate
    {
        return $this->template;
    }

    public function setTemplate(?IndexTemplate $template): void
    {
        $this->template = $template;
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
}
