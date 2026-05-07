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

namespace Gally\Metadata\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Gally\User\Constant\Role;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new Get(security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
        new Put(security: "is_granted('" . Role::ROLE_ADMIN . "')"),
        new Patch(security: "is_granted('" . Role::ROLE_ADMIN . "')"),
        new Delete(security: "is_granted('" . Role::ROLE_ADMIN . "')"),
        new GetCollection(security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
        new Post(security: "is_granted('" . Role::ROLE_ADMIN . "')"),
    ],
    graphQlOperations: [
        new Query(name: 'item_query', security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
        new QueryCollection(name: 'collection_query', security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
        new Mutation(name: 'create', security: "is_granted('" . Role::ROLE_ADMIN . "')"),
        new Mutation(name: 'update', security: "is_granted('" . Role::ROLE_ADMIN . "')"),
        new Mutation(name: 'delete', security: "is_granted('" . Role::ROLE_ADMIN . "')"),
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
    #[Groups(['metadata:read', 'metadata:write'])]
    private ?bool $isTimeSeriesData;

    /** @var ArrayCollection<int, SourceField> */
    private Collection $sourceFields;

    private ?array $filterableSourceFields = null;
    private ?array $filterableInAggregationSourceFields = null;
    private ?array $sortableSourceFields = null;

    public function __construct()
    {
        $this->sourceFields = new ArrayCollection();
        $this->isTimeSeriesData = false;
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

    public function isTimeSeriesData(): bool
    {
        return $this->isTimeSeriesData ?? false;
    }

    public function setIsTimeSeriesData(bool $isTimeSeriesData): self
    {
        $this->isTimeSeriesData = $isTimeSeriesData;

        return $this;
    }

    /**
     * @return Collection<SourceField>
     */
    public function getSourceFields(): Collection
    {
        return $this->sourceFields;
    }

    public function getSourceFieldByCodes(array $codes): array
    {
        $list = [];
        foreach ($this->sourceFields as $field) {
            if (\in_array($field->getCode(), $codes, true)) {
                $list[] = $field;
            }
        }

        return $list;
    }

    /**
     * @return SourceField[]
     */
    public function getFilterableSourceFields(): array
    {
        if (!isset($this->filterableSourceFields)) {
            $this->buildSourceFieldCaches();
        }

        return $this->filterableSourceFields;
    }

    /**
     * @return SourceField[]
     */
    public function getFilterableInAggregationSourceFields(): array
    {
        if (!isset($this->filterableInAggregationSourceFields)) {
            $this->buildSourceFieldCaches();
        }

        return $this->filterableInAggregationSourceFields;
    }

    /**
     * @return SourceField[]
     */
    public function getSortableSourceFields(): array
    {
        if (!isset($this->sortableSourceFields)) {
            $this->buildSourceFieldCaches();
        }

        return $this->sortableSourceFields;
    }

    private function buildSourceFieldCaches(): void
    {
        $this->filterableSourceFields = [];
        $this->filterableInAggregationSourceFields = [];
        $this->sortableSourceFields = [];

        $criteria = (new Criteria(firstResult: 0, accessRawFieldValues: true))
            ->where(
                Criteria::expr()->orX(
                    Criteria::expr()->eq('isFilterable', true),
                    Criteria::expr()->eq('isUsedForRules', true),
                    Criteria::expr()->eq('isSortable', true),
                )
            );

        foreach ($this->sourceFields->matching($criteria) as $field) {
            $isFilterable = $field->getIsFilterable();
            $isUsedForRules = $field->getIsUsedForRules();
            $isSortable = $field->getIsSortable();

            if ($isFilterable || $isUsedForRules) {
                $this->filterableSourceFields[] = $field;
            }

            if ($isFilterable) {
                $this->filterableInAggregationSourceFields[] = $field;
            }

            if ($isSortable) {
                $this->sortableSourceFields[] = $field;
            }
        }
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
