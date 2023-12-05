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
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Gally\Metadata\Controller\BulkSourceFieldOptions;
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
            'controller' => BulkSourceFieldOptions::class,
            'path' => '/source_field_options/bulk',
            'read' => false,
            'deserialize' => false,
            'validate' => false,
            'write' => false,
            'serialize' => true,
            'status' => Response::HTTP_OK,
            'openapi_context' => [
                'summary' => 'Add source field options.',
                'description' => 'Add source field options.',
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
                                ['sourceField' => '/source_fields/1', 'code' => 'brand_A', 'defaultLabel' => 'Brand A'],
                                ['sourceField' => '/source_fields/1', 'code' => 'brand_B', 'defaultLabel' => 'Brand B'],
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
    normalizationContext: ['groups' => ['source_field_option:read']],
    denormalizationContext: ['groups' => ['source_field_option:write']],
)]
#[ApiFilter(SearchFilter::class, properties: ['sourceField' => 'exact'])]
#[ApiFilter(OrderFilter::class, properties: ['position'], arguments: ['orderParameterName' => 'order'])]
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
    #[Groups(['source_field_option:read', 'source_field_option:write'])]
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
