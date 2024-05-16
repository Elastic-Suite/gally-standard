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
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Gally\Entity\Filter\BooleanFilter;
use Gally\Entity\Filter\SearchColumnsFilter;
use Gally\Metadata\Controller\BulkSourceFields;
use Gally\Metadata\Model\SourceField\Type;
use Gally\Metadata\Model\SourceField\Weight;
use Gally\User\Constant\Role;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    collectionOperations: [
        'get' => ['security' => "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"],
        'post' => ['security' => "is_granted('" . Role::ROLE_ADMIN . "')"],
    ],
    itemOperations: [
        'get' => ['security' => "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"],
        'put' => ['security' => "is_granted('" . Role::ROLE_ADMIN . "')"],
        'delete' => ['security' => "is_granted('" . Role::ROLE_ADMIN . "')"],
        'bulk' => [
            'security' => "is_granted('" . Role::ROLE_ADMIN . "')",
            'method' => 'POST',
            'controller' => BulkSourceFields::class,
            'path' => '/source_fields/bulk',
            'read' => false,
            'deserialize' => false,
            'validate' => false,
            'write' => false,
            'serialize' => true,
            'status' => Response::HTTP_OK,
            'openapi_context' => [
                'summary' => 'Add source fields.',
                'description' => 'Add source fields.',
                'requestBody' => [
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'string',
                                ],
                            ],
                            'example' => [
                                ['sourceField' => '/metadata/1', 'code' => 'brand', 'type' => 'text', 'defaultLabel' => 'Brand'],
                                ['sourceField' => '/metadata/1', 'code' => 'color', 'type' => 'select', 'defaultLabel' => 'Color'],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    graphql: [
        'item_query' => ['security' => "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"],
        'collection_query' => ['security' => "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"],
        'create' => ['security' => "is_granted('" . Role::ROLE_ADMIN . "')"],
        'update' => ['security' => "is_granted('" . Role::ROLE_ADMIN . "')"],
        'delete' => ['security' => "is_granted('" . Role::ROLE_ADMIN . "')"],
    ],
    normalizationContext: ['groups' => ['source_field:read']],
    denormalizationContext: ['groups' => ['source_field:write']],
)]

#[ApiFilter(SearchFilter::class, properties: ['code' => 'ipartial', 'type' => 'exact', 'metadata.entity' => 'exact', 'weight' => 'exact', 'search' => 'ipartial'])]
#[ApiFilter(SearchColumnsFilter::class, properties: ['defaultLabel' => ['code', 'labels.label']])]
#[ApiFilter(BooleanFilter::class, properties: ['isSearchable', 'isFilterable', 'isSortable', 'isUsedInAutocomplete',  'isSpellchecked', 'isUsedForRules'], arguments: ['treatNullAsFalse' => true])]
class SourceField
{
    #[Groups(['source_field:read', 'facet_configuration:graphql_read'])]
    private int $id;

    #[ApiProperty(
        attributes: [
            'hydra:supportedProperty' => [
                'hydra:property' => [
                    'rdfs:label' => 'Attribute code',
                ],
                'gally' => [
                    'visible' => true,
                    'editable' => false,
                    'position' => 10,
                ],
            ],
        ],
    )]
    #[Groups(['source_field:read', 'source_field:write', 'facet_configuration:graphql_read'])]
    private string $code;

    #[ApiProperty(
        attributes: [
            'hydra:supportedProperty' => [
                'hydra:property' => [
                    'rdfs:label' => 'Attribute label',
                ],
                'gally' => [
                    'visible' => true,
                    'editable' => false,
                    'position' => 20,
                ],
            ],
        ],
    )]
    #[Groups(['source_field:read', 'source_field:write', 'facet_configuration:graphql_read'])]
    private ?string $defaultLabel = null;

    #[ApiProperty(
        attributes: [
            'hydra:supportedProperty' => [
                'hydra:property' => [
                    'rdfs:label' => 'Attribute type',
                ],
                'gally' => [
                    'visible' => true,
                    'editable' => false,
                    'position' => 30,
                    'input' => 'select',
                    'options' => [
                        'values' => Type::AVAILABLE_TYPES_OPTIONS,
                    ],
                    'context' => [
                        'search_configuration_attributes' => [
                            'visible' => false,
                        ],
                    ],
                ],
            ],
        ],
    )]
    #[Groups(['source_field:read', 'source_field:write', 'facet_configuration:graphql_read'])]
    private ?string $type = null;

    #[ApiProperty(
        attributes: [
            'hydra:supportedProperty' => [
                'hydra:property' => [
                    'rdfs:label' => 'Filterable',
                ],
                'gally' => [
                    'visible' => true,
                    'editable' => true,
                    'position' => 40,
                    'context' => [
                        'search_configuration_attributes' => [
                            'visible' => false,
                        ],
                    ],
                ],
            ],
        ],
    )]
    #[Groups(['source_field:read', 'source_field:write', 'facet_configuration:graphql_read'])]
    private ?bool $isFilterable = null;

    #[ApiProperty(
        attributes: [
            'hydra:supportedProperty' => [
                'hydra:property' => [
                    'rdfs:label' => 'Searchable',
                ],
                'gally' => [
                    'visible' => true,
                    'editable' => true,
                    'position' => 50,
                    'context' => [
                        'search_configuration_attributes' => [
                            'visible' => false,
                        ],
                    ],
                ],
            ],
        ],
    )]
    #[Groups(['source_field:read', 'source_field:write', 'facet_configuration:graphql_read'])]
    private ?bool $isSearchable = null;

    #[ApiProperty(
        attributes: [
            'hydra:supportedProperty' => [
                'hydra:property' => [
                    'rdfs:label' => 'Sortable',
                ],
                'gally' => [
                    'visible' => true,
                    'editable' => true,
                    'position' => 60,
                    'context' => [
                        'search_configuration_attributes' => [
                            'visible' => false,
                        ],
                    ],
                ],
            ],
        ],
    )]
    #[Groups(['source_field:read', 'source_field:write', 'facet_configuration:graphql_read'])]
    private ?bool $isSortable = null;

    #[ApiProperty(
        attributes: [
            'hydra:supportedProperty' => [
                'hydra:property' => [
                    'rdfs:label' => 'Use in rule engine',
                ],
                'gally' => [
                    'visible' => true,
                    'editable' => true,
                    'position' => 70,
                    'context' => [
                        'search_configuration_attributes' => [
                            'visible' => false,
                        ],
                    ],
                ],
            ],
        ],
    )]
    #[Groups(['source_field:read', 'source_field:write', 'facet_configuration:graphql_read'])]
    private ?bool $isUsedForRules = null;

    #[ApiProperty(
        attributes: [
            'hydra:supportedProperty' => [
                'hydra:property' => [
                    'rdfs:label' => 'Search weight',
                ],
                'gally' => [
                    'visible' => false,
                    'editable' => true,
                    'position' => 80,
                    'input' => 'select',
                    'options' => [
                        'values' => Weight::WEIGHT_VALID_VALUES_OPTIONS,
                    ],
                    'context' => [
                        'search_configuration_attributes' => [
                            'visible' => true,
                        ],
                    ],
                ],
            ],
        ],
    )]
    #[Groups(['source_field:read', 'source_field:write', 'facet_configuration:graphql_read'])]
    private int $weight = 1;

    #[ApiProperty(
        attributes: [
            'hydra:supportedProperty' => [
                'hydra:property' => [
                    'rdfs:label' => 'Used in spellcheck',
                ],
                'gally' => [
                    'visible' => false,
                    'editable' => true,
                    'position' => 90,
                    'context' => [
                        'search_configuration_attributes' => [
                            'visible' => true,
                        ],
                    ],
                ],
            ],
        ],
    )]
    #[Groups(['source_field:read', 'source_field:write', 'facet_configuration:graphql_read'])]
    private ?bool $isSpellchecked = null;

    #[ApiProperty(
        attributes: [
            'hydra:supportedProperty' => [
                'hydra:property' => [
                    'rdfs:label' => 'Displayed in autocomplete',
                ],
                'gally' => [
                    'visible' => true,
                    'editable' => true,
                    'position' => 100,
                    'context' => [
                        'search_configuration_attributes' => [
                            'visible' => false,
                        ],
                    ],
                ],
            ],
        ],
    )]
    #[Groups(['source_field:read', 'source_field:write', 'facet_configuration:graphql_read'])]
    private ?bool $isUsedInAutocomplete = null;

    #[Groups(['source_field:read', 'source_field:write', 'facet_configuration:graphql_read'])]
    private bool $isSystem = false;

    #[Groups(['source_field:read', 'source_field:write', 'facet_configuration:graphql_read'])]
    private Metadata $metadata;

    private ?bool $isNested = null;

    private ?string $nestedPath = null;

    private ?string $nestedCode = null;

    private ?string $search = null;

    /** @var Collection<SourceFieldLabel> */
    #[Groups(['source_field:read', 'source_field:write'])]
    private Collection $labels;

    /** @var Collection<SourceFieldOption> */
    #[Groups(['facet_configuration:graphql_read'])]
    private Collection $options;

    public function __construct()
    {
        $this->labels = new ArrayCollection();
        $this->options = new ArrayCollection();
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code ?? null;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getDefaultLabel(): string
    {
        foreach ($this->getLabels() as $label) {
            if ($label->getLocalizedCatalog()->getIsDefault()) {
                return $label->getLabel();
            }
        }

        return $this->defaultLabel ?: ucfirst($this->getCode());
    }

    public function setDefaultLabel(?string $defaultLabel): self
    {
        $this->defaultLabel = $defaultLabel;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getWeight(): ?int
    {
        return $this->weight;
    }

    public function setWeight(?int $weight): self
    {
        $this->weight = $weight;

        return $this;
    }

    public function getIsSearchable(): ?bool
    {
        return $this->isSearchable;
    }

    public function setIsSearchable(?bool $isSearchable): self
    {
        $this->isSearchable = $isSearchable;

        return $this;
    }

    public function getIsFilterable(): ?bool
    {
        return $this->isFilterable;
    }

    public function setIsFilterable(?bool $isFilterable): self
    {
        $this->isFilterable = $isFilterable;

        return $this;
    }

    public function getIsSortable(): ?bool
    {
        return $this->isSortable;
    }

    public function setIsSortable(?bool $isSortable): self
    {
        $this->isSortable = $isSortable;

        return $this;
    }

    public function getIsSpellchecked(): ?bool
    {
        return $this->isSpellchecked;
    }

    public function setIsSpellchecked(?bool $isSpellchecked): self
    {
        $this->isSpellchecked = $isSpellchecked;

        return $this;
    }

    public function getIsUsedForRules(): ?bool
    {
        return $this->isUsedForRules;
    }

    public function setIsUsedForRules(?bool $isUsedForRules): self
    {
        $this->isUsedForRules = $isUsedForRules;

        return $this;
    }

    public function getIsUsedInAutocomplete(): ?bool
    {
        return $this->isUsedInAutocomplete;
    }

    public function setIsUsedInAutocomplete(?bool $isUsedInAutocomplete): self
    {
        $this->isUsedInAutocomplete = $isUsedInAutocomplete;

        // If the sourceField is set as used in autocomplete, we force it to be filterable.
        $this->isFilterable = $this->isUsedInAutocomplete ?: $this->isFilterable;

        return $this;
    }

    public function getIsSystem(): bool
    {
        return $this->isSystem;
    }

    public function setIsSystem(bool $isSystem): self
    {
        $this->isSystem = $isSystem;

        return $this;
    }

    public function getSearch(): ?string
    {
        return $this->search;
    }

    public function setSearch(?string $search): self
    {
        $this->search = $search;

        return $this;
    }

    public function getMetadata(): ?Metadata
    {
        return $this->metadata ?? null;
    }

    public function setMetadata(?Metadata $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function isNested(): bool
    {
        if (null == $this->isNested) {
            $this->isNested = str_contains($this->getCode(), '.');
        }

        return $this->isNested;
    }

    public function getNestedPath(): ?string
    {
        if ($this->isNested() && (null === $this->nestedPath)) {
            // Alternative: all elements minus the last one.
            $path = explode('.', $this->getCode());
            $this->nestedPath = current($path);
        }

        return $this->nestedPath;
    }

    public function getNestedCode(): ?string
    {
        if (null === $this->nestedCode) {
            $this->nestedCode = $this->getCode();
            if ($this->isNested() && (null !== $this->getNestedPath())) {
                $this->nestedCode = substr($this->nestedCode, \strlen($this->getNestedPath()) + 1);
            }
        }

        return $this->nestedCode;
    }

    /**
     * @return Collection<SourceFieldLabel>
     */
    public function getLabels(): Collection
    {
        return $this->labels;
    }

    public function setLabels(Collection $labels): self
    {
        $this->labels = $labels;

        return $this;
    }

    public function getLabel(int $catalogId): string
    {
        foreach ($this->getLabels() as $label) {
            if ($catalogId === $label->getLocalizedCatalog()->getId()) {
                return $label->getLabel();
            }
        }

        return $this->getDefaultLabel();
    }

    public function addLabel(SourceFieldLabel $label): self
    {
        if (!$this->labels->contains($label)) {
            $this->labels[] = $label;
            $label->setSourceField($this);
        }

        return $this;
    }

    public function removeLabel(SourceFieldLabel $label): self
    {
        $this->labels->removeElement($label);

        return $this;
    }

    /**
     * @return Collection<SourceFieldOption>
     */
    public function getOptions(): Collection
    {
        return $this->options;
    }

    public function addOption(SourceFieldOption $option): self
    {
        if (!$this->options->contains($option)) {
            $this->options[] = $option;
            $option->setSourceField($this);
        }

        return $this;
    }

    public function removeOption(SourceFieldOption $option): self
    {
        if ($this->options->removeElement($option)) {
            if ($option->getSourceField() === $this) {
                $option->setSourceField(null);
            }
        }

        return $this;
    }
}
