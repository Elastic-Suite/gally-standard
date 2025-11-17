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
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Gally\Metadata\Controller\BulkSourceFieldOptions;
use Gally\Metadata\Operation\Bulk;
use Gally\Metadata\State\SourceFieldOptionProcessor;
use Gally\User\Constant\Role;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new Get(security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
        new Put(security: "is_granted('" . Role::ROLE_ADMIN . "')"),
        new Delete(security: "is_granted('" . Role::ROLE_ADMIN . "')"),
        new Bulk(security: "is_granted('" . Role::ROLE_ADMIN . "')",
            controller: BulkSourceFieldOptions::class,
            uriTemplate: '/source_field_options/bulk',
            read: false,
            deserialize: false,
            validate: false,
            write: false,
            serialize: true,
            status: 200,
            openapiContext: [
                'summary' => 'Add source field options.',
                'description' => 'Add source field options.',
                'requestBody' => [
                    'content' => [
                        'application/json' => [
                            'schema' => ['type' => 'array', 'items' => ['type' => 'string']],
                            'example' => [
                                [
                                    'sourceField' => '/source_fields/1',
                                    'code' => 'brand_A',
                                    'defaultLabel' => 'Brand A',
                                    'labels' => [
                                        ['localizedCatalog' => '/localized_catalogs/1', 'label' => 'Marque A'],
                                        ['localizedCatalog' => '/localized_catalogs/7', 'label' => 'Marca A'],
                                    ],
                                ],
                                [
                                    'sourceField' => '/source_fields/1',
                                    'code' => 'brand_B',
                                    'defaultLabel' => 'Brand B',
                                    'labels' => [
                                        ['localizedCatalog' => '/localized_catalogs/1', 'label' => 'Marque B'],
                                        ['localizedCatalog' => '/localized_catalogs/7', 'label' => 'Marca B'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        ),
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
    processor: SourceFieldOptionProcessor::class,
    denormalizationContext: ['groups' => ['source_field_option:write']],
    normalizationContext: ['groups' => ['source_field_option:read']]
)]

#[ApiFilter(filterClass: SearchFilter::class, properties: ['sourceField' => 'exact'])]
#[ApiFilter(filterClass: OrderFilter::class, properties: ['position'], arguments: ['orderParameterName' => 'order'])]
class SourceFieldOption
{
    #[Groups(['source_field_option:read', 'source_field_option:write', 'source_field_option_label:read'])]
    private int $id;

    #[Groups(['source_field_option:read', 'source_field_option:write', 'source_field_option_label:read'])]
    private string $code;

    #[Groups(['source_field_option:read', 'source_field_option:write', 'source_field_option_label:read'])]
    private SourceField $sourceField;

    #[Groups(['source_field_option:read', 'source_field_option:write', 'source_field_option_label:read'])]
    private ?int $position;

    #[Groups(['source_field_option:read', 'source_field_option:write', 'source_field_option_label:read'])]
    private string $defaultLabel;

    /** @var Collection<SourceFieldOptionLabel> */
    #[Groups(['source_field_option:write'])]
    private Collection $labels;

    public function __construct()
    {
        $this->labels = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id ?? null;
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

    public function getSourceField(): ?SourceField
    {
        return $this->sourceField ?? null;
    }

    public function setSourceField(?SourceField $sourceField): self
    {
        $this->sourceField = $sourceField;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position ?? null;
    }

    public function setPosition(?int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getDefaultLabel(): ?string
    {
        return $this->defaultLabel ?? null;
    }

    public function setDefaultLabel(string $defaultLabel): self
    {
        $this->defaultLabel = $defaultLabel;

        return $this;
    }

    /**
     * @return Collection<SourceFieldOptionLabel>
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

    public function addLabel(SourceFieldOptionLabel $label): self
    {
        if (!$this->labels->contains($label)) {
            $this->labels[] = $label;
            $label->setSourceFieldOption($this);
        }

        return $this;
    }

    public function removeLabel(SourceFieldOptionLabel $label): self
    {
        $this->labels->removeElement($label);

        return $this;
    }
}
