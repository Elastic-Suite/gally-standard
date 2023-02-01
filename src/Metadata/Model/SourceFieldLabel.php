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

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Gally\Catalog\Model\LocalizedCatalog;
use Gally\User\Constant\Role;

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
)]
#[ApiFilter(SearchFilter::class, properties: ['localizedCatalog' => 'exact', 'sourceField' => 'exact'])]
class SourceFieldLabel
{
    private int $id;
    private SourceField $sourceField;
    private LocalizedCatalog $localizedCatalog;
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
