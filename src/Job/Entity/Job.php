<?php
/**
 * DISCLAIMER.
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Licensed to Smile-SA. All rights reserved. No warranty, explicit or implicit, provided.
 *            Unauthorized copying of this file, via any medium, is strictly prohibited.
 */

declare(strict_types=1);

namespace Gally\Job\Entity;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Gally\Boost\Entity\Boost\CategoryLimitation;
use Gally\Boost\Entity\Boost\RequestType;
use Gally\Boost\Entity\Boost\SearchLimitation;
use Gally\Boost\State\BoostProcessor;
use Gally\Boost\State\BoostProvider;
use Gally\Catalog\Entity\LocalizedCatalog;
use Gally\Job\Entity\Job\ImportFile;
use Gally\Job\Entity\Job\Log;
use Gally\User\Constant\Role;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new Get(security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
        new Delete(security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
        new GetCollection(security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
        new Post(security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
    ],
    graphQlOperations: [
        new Query(name: 'item_query', security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
        new QueryCollection(name: 'collection_query', security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
        new Mutation(name: 'create', security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
        new Mutation(name: 'delete', security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
    ],
    denormalizationContext: ['groups' => ['job:write']],
    normalizationContext: ['groups' => ['job:read']],
)]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['type' => 'exact', 'profile' => 'exact', 'status' => 'exact'])]
class Job
{
    use TimestampableEntity;

    #[Groups(['job:read', 'job:write'])]
    private ?int $id = null;

    #[ApiProperty(
        extraProperties: [
            'hydra:supportedProperty' => [
                'hydra:property' => [
                    'rdfs:label' => 'Type',
                ],
                'gally' => [
                    'visible' => true,
                    'editable' => false,
                    'position' => 10,
                ],
            ],
        ],
    )]
    #[Groups(['job:read', 'job:write'])]
    private string $type;

    #[ApiProperty(
        extraProperties: [
            'hydra:supportedProperty' => [
                'hydra:property' => [
                    'rdfs:label' => 'Profile',
                ],
                'gally' => [
                    'visible' => true,
                    'editable' => false,
                    'position' => 20,
                ],
            ],
        ],
    )]
    #[Groups(['job:read', 'job:write'])]
    private string $profile;

    #[ApiProperty(
        extraProperties: [
            'hydra:supportedProperty' => [
                'hydra:property' => [
                    'rdfs:label' => 'Status',
                ],
                'gally' => [
                    'visible' => true,
                    'editable' => false,
                    'position' => 30,
                ],
            ],
        ],
    )]
    #[Groups(['job:read'])]
    private string $status;

    /** @var \Doctrine\Common\Collections\Collection&iterable<Log> */
    #[Groups(['job:read'])]
    private Collection $logs;

    /** @var \Doctrine\Common\Collections\Collection&iterable<ImportFile> */
    #[Groups(['job:read'])]
    private Collection $ImportFile;

    #[Groups(['job:read'])]
    protected $createdAt;

    #[Groups(['boost:read'])]
    protected $updatedAt;

    #[Groups(['job:read'])]
    protected ?\DateTime $finishedAt;

    public function __construct()
    {
        $this->localizedCatalogs = new ArrayCollection();
        $this->requestTypes = new ArrayCollection();
        $this->categoryLimitations = new ArrayCollection();
        $this->searchLimitations = new ArrayCollection();
    }

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

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getFromDate(): ?\DateTime
    {
        return $this->fromDate;
    }

    public function setFromDate(?\DateTime $fromDate): self
    {
        $this->fromDate = $fromDate;

        return $this;
    }

    public function getToDate(): ?\DateTime
    {
        return $this->toDate;
    }

    public function setToDate(?\DateTime $toDate): self
    {
        $this->toDate = $toDate;

        return $this;
    }

    public function getConditionRule(): ?string
    {
        return $this->conditionRule;
    }

    public function setConditionRule(?string $conditionRule): self
    {
        $this->conditionRule = $conditionRule;

        return $this;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function setModel(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function getModelConfigValue(string $key, $defaultValue = null): mixed
    {
        return json_decode($this->modelConfig, true)[$key] ?? $defaultValue;
    }

    public function getModelConfig(): string
    {
        return $this->modelConfig;
    }

    public function setModelConfig(string $modelConfig): self
    {
        $this->modelConfig = $modelConfig;

        return $this;
    }

    #[ApiProperty(
        extraProperties: [
            'hydra:supportedProperty' => [
                'hydra:property' => [
                    'rdfs:label' => 'Localized catalog(s)',
                ],
                'gally' => [
                    'visible' => true,
                    'editable' => false,
                    'position' => 60,
                    'input' => 'optgroup',
                    'options' => [
                        'api_rest' => '/localized_catalog_group_options',
                        'api_graphql' => 'localizedCatalogGroupOptions',
                    ],
                    'alias' => 'localizedCatalogs.id',
                    'form' => [
                        'visible' => false,
                    ],
                ],
            ],
        ],
    )]
    #[Groups(['job:read'])]
    public function getLocalizedCatalogLabels(): array
    {
        $localizedCatalogLabels = [];
        foreach ($this->localizedCatalogs as $localizedCatalog) {
            $localizedCatalogLabels[] = $localizedCatalog->getCatalog()->getName() . ' - ' . $localizedCatalog->getName();
        }

        return $localizedCatalogLabels;
    }

    /**
     * @return Collection|LocalizedCatalog[]
     */
    public function getLocalizedCatalogs(): Collection
    {
        return $this->localizedCatalogs;
    }

    public function addLocalizedCatalog(LocalizedCatalog $localizedCatalog): self
    {
        if (!$this->localizedCatalogs->contains($localizedCatalog)) {
            $this->localizedCatalogs[] = $localizedCatalog;
            // Reset array keys to keep localizedCatalogs as a json array during the normalization.
            $this->localizedCatalogs = new ArrayCollection($this->localizedCatalogs->getValues());
        }

        return $this;
    }

    public function removeLocalizedCatalog(LocalizedCatalog $localizedCatalog): self
    {
        $this->localizedCatalogs->removeElement($localizedCatalog);
        // Reset array keys to keep localizedCatalogs as a json array during the normalization.
        $this->localizedCatalogs = new ArrayCollection($this->localizedCatalogs->getValues());

        return $this;
    }

    public function getRequestTypeLabels(): array
    {
        return $this->requestTypeLabels;
    }

    public function setRequestTypeLabels(array $requestTypeLabels): self
    {
        $this->requestTypeLabels = $requestTypeLabels;

        return $this;
    }

    /**
     * @return Collection|RequestType[]
     */
    public function getRequestTypes(): Collection
    {
        return $this->requestTypes;
    }

    public function setRequestTypes(Collection $requestTypes): self
    {
        $this->requestTypes = $requestTypes;

        return $this;
    }

    public function addRequestType(RequestType $requestType): self
    {
        if (!$this->requestTypes->contains($requestType)) {
            $this->requestTypes[] = $requestType;
            $requestType->setBoost($this);
            // Reset array keys to keep requestTypes as a json array during the normalization.
            $this->requestTypes = new ArrayCollection($this->requestTypes->getValues());
        }

        return $this;
    }

    public function removeRequestType(RequestType $requestType): self
    {
        if ($this->requestTypes->contains($requestType)) {
            $this->requestTypes->removeElement($requestType);
            // Reset array keys to keep requestTypes as a json array during the normalization.
            $this->requestTypes = new ArrayCollection($this->requestTypes->getValues());
        }

        return $this;
    }

    public function setCategoryLimitations(Collection $categoryLimitations): self
    {
        $this->categoryLimitations = $categoryLimitations;

        return $this;
    }

    /**
     * @return Collection|CategoryLimitation[]
     */
    public function getCategoryLimitations(): Collection
    {
        return $this->categoryLimitations;
    }

    public function addCategoryLimitation(CategoryLimitation $categoryLimitation): self
    {
        if (!$this->categoryLimitations->contains($categoryLimitation)) {
            $this->categoryLimitations[] = $categoryLimitation;
            $categoryLimitation->setBoost($this);
            // Reset array keys to keep categoryLimitations as a json array during the normalization.
            $this->categoryLimitations = new ArrayCollection($this->categoryLimitations->getValues());
        }

        return $this;
    }

    public function removeCategoryLimitation(CategoryLimitation $categoryLimitation): self
    {
        if ($this->categoryLimitations->contains($categoryLimitation)) {
            $this->categoryLimitations->removeElement($categoryLimitation);
            // Reset array keys to keep categoryLimitations as a json array during the normalization.
            $this->categoryLimitations = new ArrayCollection($this->categoryLimitations->getValues());
        }

        return $this;
    }

    /**
     * @return Collection|SearchLimitation[]
     */
    public function getSearchLimitations(): Collection
    {
        // Reset array keys to keep searchLimitations as a json array during the normalization.
        return new ArrayCollection($this->searchLimitations->getValues());
    }

    public function addSearchLimitation(SearchLimitation $searchLimitation): self
    {
        if (!$this->searchLimitations->contains($searchLimitation)) {
            $this->searchLimitations[] = $searchLimitation;
            $searchLimitation->setBoost($this);
        }

        return $this;
    }

    public function removeSearchLimitation(SearchLimitation $searchLimitation): self
    {
        if ($this->searchLimitations->contains($searchLimitation)) {
            $this->searchLimitations->removeElement($searchLimitation);
        }

        return $this;
    }

    #[ApiProperty(
        extraProperties: [
            'hydra:supportedProperty' => [
                'hydra:property' => [
                    'rdfs:label' => 'Boost Preview',
                ],
                'gally' => [
                    'fieldset' => 'preview',
                    'visible' => false,
                    'editable' => false,
                    'position' => 40,
                    'input' => 'boostPreview',
                    'form' => [
                        'visible' => true,
                    ],
                ],
            ],
        ],
    )]
    #[Groups(['job:read'])]
    /**
     * Property used only to display boost preview in front-office.
     */
    public function getPreview(): string
    {
        return '';
    }
}
