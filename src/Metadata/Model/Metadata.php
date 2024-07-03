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

namespace Gally\Metadata\Model;

use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Gally\User\Constant\Role;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new Get(security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
        new Put(security: "is_granted('" . Role::ROLE_ADMIN . "')"),
        new Patch(security: "is_granted('" . Role::ROLE_ADMIN . "')"),
        new Delete(security: "is_granted('" . Role::ROLE_ADMIN . "')"),
        new GetCollection(security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
        new Post(security: "is_granted('" . Role::ROLE_ADMIN . "')")
    ],
    graphQlOperations: [
        new Query(name: 'item_query', security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
        new QueryCollection(name: 'collection_query', security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
        new Mutation(name: 'create', security: "is_granted('" . Role::ROLE_ADMIN . "')"),
        new Mutation(name: 'update', security: "is_granted('" . Role::ROLE_ADMIN . "')"),
        new Mutation(name: 'delete', security: "is_granted('" . Role::ROLE_ADMIN . "')")
    ],
    denormalizationContext: ['groups' => ['metadata:write']],
    normalizationContext: ['groups' => ['metadata:read']]
)]
class Metadata
{
    #[Groups(['metadata:read'])]
    private int $id;
    #[Groups(['metadata:read', 'metadata:write'])]
    private string $entity;

    /** @var Collection<SourceField> */
    private Collection $sourceFields;

    public function __construct()
    {
        $this->sourceFields = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEntity(): ?string
    {
        return $this->entity;
    }

    public function setEntity(string $entity): self
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * @return Collection<SourceField>
     */
    public function getSourceFields(): Collection
    {
        return $this->sourceFields;
    }

    public function addSourceField(SourceField $sourceField): self
    {
        if (!$this->sourceFields->contains($sourceField)) {
            $this->sourceFields[] = $sourceField;
            $sourceField->setMetadata($this);
        }

        return $this;
    }

    public function removeSourceField(SourceField $sourceField): self
    {
        if ($this->sourceFields->removeElement($sourceField)) {
            if ($sourceField->getMetadata() === $this) {
                $sourceField->setMetadata(null);
            }
        }

        return $this;
    }
}
