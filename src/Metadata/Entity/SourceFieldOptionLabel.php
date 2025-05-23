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

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
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
use Gally\Catalog\Entity\LocalizedCatalog;
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
    denormalizationContext: ['groups' => ['source_field_option_label:write']],
    normalizationContext: ['groups' => ['source_field_option_label:read']],
)]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['localizedCatalog' => 'exact', 'sourceFieldOption' => 'exact', 'sourceFieldOption.sourceField' => 'exact'])]
#[ApiFilter(filterClass: OrderFilter::class, properties: ['sourceFieldOption.position'], arguments: ['orderParameterName' => 'order'])]
class SourceFieldOptionLabel
{
    #[Groups(['source_field_option_label:read', 'source_field_option_label:write'])]
    private int $id;

    #[Groups(['source_field_option_label:read', 'source_field_option_label:write'])]
    private SourceFieldOption $sourceFieldOption;

    #[Groups(['source_field_option_label:read', 'source_field_option_label:write', 'source_field_option:read', 'source_field_option:write'])]
    private LocalizedCatalog $localizedCatalog;

    #[Groups(['source_field_option_label:read', 'source_field_option_label:write', 'source_field_option:read', 'source_field_option:write'])]
    private string $label;

    public function getId(): int
    {
        return $this->id;
    }

    public function getSourceFieldOption(): ?SourceFieldOption
    {
        return $this->sourceFieldOption;
    }

    public function setSourceFieldOption(SourceFieldOption $sourceFieldOption): self
    {
        $this->sourceFieldOption = $sourceFieldOption;

        return $this;
    }

    public function getLocalizedCatalog(): ?LocalizedCatalog
    {
        return $this->localizedCatalog;
    }

    public function setLocalizedCatalog(?LocalizedCatalog $localizedCatalog): self
    {
        $this->localizedCatalog = $localizedCatalog;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): self
    {
        $this->label = $label;

        return $this;
    }
}
