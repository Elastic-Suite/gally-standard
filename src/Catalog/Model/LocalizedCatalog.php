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
use Gally\Catalog\State\LocalizedCatalogProcessor;
use Gally\User\Constant\Role;
use Symfony\Component\Intl\Exception\MissingResourceException;
use Symfony\Component\Intl\Locales;
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
    processor: LocalizedCatalogProcessor::class,
    normalizationContext: ['groups' => ['localizedCatalog:read']]
)]

#[ApiFilter(filterClass: SearchFilter::class, properties: ['code' => 'exact'])]
class LocalizedCatalog
{
    #[Groups(['localizedCatalog:read', 'catalog:read', 'source_field_option:read'])]
    private int $id;

    #[Groups(['localizedCatalog:read', 'catalog:read'])]
    private ?string $name;

    #[Groups(['localizedCatalog:read', 'catalog:read', 'source_field_option:read'])]
    private string $code;

    #[Groups(['localizedCatalog:read', 'catalog:read'])]
    private string $locale;

    #[Groups(['localizedCatalog:read', 'catalog:read'])]
    private string $currency;

    #[Groups(['localizedCatalog:read', 'catalog:read'])]
    private bool $isDefault = false;

    #[Groups('localizedCatalog:read')]
    private Catalog $catalog;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    /**
     * It's important to keep the getter for isDefault property,
     * otherwise Api Platform will be not able to get the value in the response,
     * in other words don't rename by IsDefault().
     */
    public function getIsDefault(): bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): self
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    public function getCatalog(): ?Catalog
    {
        return $this->catalog;
    }

    public function setCatalog(?Catalog $catalog): self
    {
        $this->catalog = $catalog;

        return $this;
    }

    #[Groups(['localizedCatalog:read', 'catalog:read'])]
    public function getLocalName(): string
    {
        try {
            $localeName = ucfirst(Locales::getName($this->getLocale()));
        } catch (MissingResourceException $e) {
            $localeName = $this->getLocale();
        }

        return $localeName;
    }
}
