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

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Gally\User\Constant\Role;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    collectionOperations: [
        'get' => ['security' => "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"],
        'post' => ['security' => "is_granted('" . Role::ROLE_ADMIN . "')"],
    ],
    itemOperations: [
        'get' => ['security' => "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"],
        'put' => ['security' => "is_granted('" . Role::ROLE_ADMIN . "')"],
        'patch' => ['security' => "is_granted('" . Role::ROLE_ADMIN . "')"],
        'delete' => ['security' => "is_granted('" . Role::ROLE_ADMIN . "')"],
    ],
    graphql: [
        'item_query' => ['security' => "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"],
        'collection_query' => ['security' => "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"],
        'create' => ['security' => "is_granted('" . Role::ROLE_ADMIN . "')"],
        'update' => ['security' => "is_granted('" . Role::ROLE_ADMIN . "')"],
        'delete' => ['security' => "is_granted('" . Role::ROLE_ADMIN . "')"],
    ],
    normalizationContext: ['groups' => ['metadata:read']],
    denormalizationContext: ['groups' => ['metadata:write']],
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
