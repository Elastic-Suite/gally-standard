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
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use Gally\Catalog\Model\LocalizedCatalog;
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
    ]
)]

#[ApiFilter(filterClass: SearchFilter::class, properties: ['localizedCatalog' => 'exact', 'sourceField' => 'exact'])]
class SourceFieldLabel
{
    private int $id;

    private SourceField $sourceField;

    #[Groups(['source_field:read', 'source_field:write'])]
    private LocalizedCatalog $localizedCatalog;

    #[Groups(['source_field:read', 'source_field:write'])]
    private string $label;

    public function getId(): int
    {
        return $this->id;
    }

    public function getSourceField(): ?SourceField
    {
        return $this->sourceField;
    }

    public function setSourceField(?SourceField $sourceField): self
    {
        $this->sourceField = $sourceField;

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
