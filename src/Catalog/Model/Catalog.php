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

namespace Gally\Catalog\Model;

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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Gally\User\Constant\Role;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new Get(),
        new Put(security: "is_granted('" . Role::ROLE_ADMIN . "')"),
        new Patch(security: "is_granted('" . Role::ROLE_ADMIN . "')"),
        new Delete(security: "is_granted('" . Role::ROLE_ADMIN . "')"),
        new GetCollection(),
        new Post(security: "is_granted('" . Role::ROLE_ADMIN . "')"),
    ],
    graphQlOperations: [
        new Query(name: 'item_query'),
        new QueryCollection(name: 'collection_query'),
        new Mutation(name: 'create', security: "is_granted('" . Role::ROLE_ADMIN . "')"),
        new Mutation(name: 'update', security: "is_granted('" . Role::ROLE_ADMIN . "')"),
        new Mutation(name: 'delete', security: "is_granted('" . Role::ROLE_ADMIN . "')")],
    normalizationContext: ['groups' => ['catalog:read']]
)]

#[ApiFilter(filterClass: SearchFilter::class, properties: ['code' => 'exact'])]
class Catalog
{
    #[Groups('catalog:read')]
    private int $id;

    #[Groups('catalog:read')]
    private string $code;

    #[Groups('catalog:read')]
    private ?string $name;

    /** @var \Doctrine\Common\Collections\Collection&iterable<\Gally\Catalog\Model\LocalizedCatalog> */
    #[Groups('catalog:read')]
    private Collection $localizedCatalogs;

    public function __construct()
    {
        $this->localizedCatalogs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, LocalizedCatalog>|LocalizedCatalog[]
     */
    public function getLocalizedCatalogs(): Collection
    {
        return $this->localizedCatalogs;
    }

    public function addLocalizedCatalog(LocalizedCatalog $localizedCatalog): self
    {
        if (!$this->localizedCatalogs->contains($localizedCatalog)) {
            $this->localizedCatalogs[] = $localizedCatalog;
            $localizedCatalog->setCatalog($this);
        }

        return $this;
    }

    public function removeLocalizedCatalog(LocalizedCatalog $localizedCatalog): self
    {
        if ($this->localizedCatalogs->removeElement($localizedCatalog)) {
            if ($localizedCatalog->getCatalog() === $this) {
                $localizedCatalog->setCatalog(null);
            }
        }

        return $this;
    }
}
