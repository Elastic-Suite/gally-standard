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

namespace Gally\Search\Entity\Facet;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Put;
use Gally\Category\Entity\Category;
use Gally\Doctrine\Filter\RangeFilterWithDefault;
use Gally\Doctrine\Filter\SearchFilterWithDefault;
use Gally\Doctrine\Filter\VirtualSearchFilter;
use Gally\Metadata\Entity\SourceField;
use Gally\Search\Elasticsearch\Request\BucketInterface;
use Gally\Search\State\Facet\ConfigurationCollectionProvider;
use Gally\Search\State\Facet\ConfigurationItemProvider;
use Gally\Search\State\Facet\ConfigurationProcessor;
use Gally\User\Constant\Role;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new Get(
            security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')",
            provider: ConfigurationItemProvider::class,
        ),
        new Put(
            security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')",
            normalizationContext: [
                'groups' => ['facet_configuration:read'],
            ],
            denormalizationContext: [
                'groups' => ['facet_configuration:write'],
            ],
        ),
        new Patch(
            security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')",
            normalizationContext: [
                'groups' => ['facet_configuration:read'],
            ],
            denormalizationContext: [
                'groups' => ['facet_configuration:write'],
            ]
        ),
        new Delete(security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
        new GetCollection(
            security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')",
            provider: ConfigurationCollectionProvider::class,
        ),
    ],
    graphQlOperations: [
        new Query(
            name: 'item_query',
            security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')",
            normalizationContext: [
                'groups' => ['facet_configuration:read', 'facet_configuration:graphql_read'],
            ],
            denormalizationContext: [
                'groups' => ['facet_configuration:read', 'facet_configuration:graphql_read']]
        ),
        new QueryCollection(
            name: 'collection_query',
            provider: ConfigurationCollectionProvider::class,
            security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')",
            normalizationContext: ['groups' => ['facet_configuration:read', 'facet_configuration:graphql_read']],
            denormalizationContext: ['groups' => ['facet_configuration:read', 'facet_configuration:graphql_read']],
            paginationType: 'page'
        ),
        new Mutation(
            name: 'update',
            security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')",
            normalizationContext: ['groups' => ['facet_configuration:read', 'facet_configuration:graphql_read']],
            denormalizationContext: ['groups' => ['facet_configuration:write']]),
        new Mutation(
            name: 'delete',
            security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"
        ),
    ],
    provider: ConfigurationItemProvider::class,
    processor: ConfigurationProcessor::class,
    shortName: 'FacetConfiguration',
    extraProperties : ['gally' => [
        'cache_tag' => ['resource_classes' => [SourceField::class]]],
    ],
    denormalizationContext: ['groups' => ['facet_configuration:read']],
    normalizationContext: ['groups' => ['facet_configuration:read']]
)]
#[ApiFilter(filterClass: VirtualSearchFilter::class, properties: ['search' => ['type' => 'string', 'strategy' => 'ipartial']])]
#[ApiFilter(filterClass: SearchFilterWithDefault::class, properties: ['sourceField.metadata.entity' => 'exact', 'category' => 'exact', 'displayMode' => 'exact', 'sortOrder' => 'exact'], arguments: ['defaultValues' => self::DEFAULT_VALUES])]
#[ApiFilter(filterClass: RangeFilterWithDefault::class, properties: ['coverageRate', 'maxSize', 'position'], arguments: ['defaultValues' => self::DEFAULT_VALUES])]
class Configuration
{
    public const DISPLAY_MODE_AUTO = 'auto';
    public const DISPLAY_MODE_ALWAYS_DISPLAYED = 'displayed';
    public const DISPLAY_MODE_ALWAYS_HIDDEN = 'hidden';

    public const FILTER_LOGICAL_OPERATOR_OR = 'OR';
    public const FILTER_LOGICAL_OPERATOR_AND = 'AND';

    private const DEFAULT_VALUES = [
        'displayMode' => self::DISPLAY_MODE_AUTO,
        'coverageRate' => 90,
        'maxSize' => 10,
        'sortOrder' => BucketInterface::SORT_ORDER_COUNT,
        'isRecommendable' => false,
        'isVirtual' => false,
        'position' => null,
        'booleanLogic' => self::FILTER_LOGICAL_OPERATOR_OR,
    ];

    #[Groups(['facet_configuration:read'])]
    private string $id;

    #[Groups(['facet_configuration:read'])]
    private SourceField $sourceField;

    #[Groups(['facet_configuration:read'])]
    private ?Category $category;

    #[ApiProperty(
        extraProperties: [
            'hydra:supportedProperty' => [
                'hydra:property' => [
                    'rdfs:label' => 'Display',
                ],
                'gally' => [
                    'visible' => true,
                    'editable' => true,
                    'position' => 30,
                    'input' => 'select',
                    'options' => [
                        'values' => [
                            ['value' => self::DISPLAY_MODE_AUTO, 'label' => 'Auto'],
                            ['value' => self::DISPLAY_MODE_ALWAYS_DISPLAYED, 'label' => 'Displayed'],
                            ['value' => self::DISPLAY_MODE_ALWAYS_HIDDEN, 'label' => 'Hidden'],
                        ],
                    ],
                ],
            ],
        ],
    )]
    #[Groups(['facet_configuration:read', 'facet_configuration:write'])]
    private ?string $displayMode = null;

    #[ApiProperty(
        extraProperties: [
            'hydra:supportedProperty' => [
                'hydra:property' => [
                    'rdfs:label' => 'Coverage',
                ],
                'gally' => [
                    'visible' => true,
                    'editable' => true,
                    'position' => 40,
                    'input' => 'percentage',
                    'validation' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
            ],
        ],
    )]
    #[Groups(['facet_configuration:read', 'facet_configuration:write'])]
    private ?int $coverageRate = null;

    #[ApiProperty(
        extraProperties: [
            'hydra:supportedProperty' => [
                'hydra:property' => [
                    'rdfs:label' => 'Max size',
                ],
                'gally' => [
                    'visible' => true,
                    'editable' => true,
                    'position' => 50,
                    'validation' => [
                        'min' => 0,
                    ],
                ],
            ],
        ],
    )]
    #[Groups(['facet_configuration:read', 'facet_configuration:write'])]
    private ?int $maxSize = null;

    #[ApiProperty(
        extraProperties: [
            'hydra:supportedProperty' => [
                'hydra:property' => [
                    'rdfs:label' => 'Sort order',
                ],
                'gally' => [
                    'visible' => true,
                    'editable' => true,
                    'position' => 60,
                    'input' => 'select',
                    'options' => [
                        'values' => [
                            ['value' => BucketInterface::SORT_ORDER_COUNT, 'label' => 'Result count'],
                            ['value' => BucketInterface::SORT_ORDER_MANUAL, 'label' => 'Admin sort'],
                            ['value' => BucketInterface::SORT_ORDER_TERM, 'label' => 'Name (A → Z)'],
                            ['value' => BucketInterface::SORT_ORDER_TERM_DESC, 'label' => 'Name (Z → A)'],
                            ['value' => BucketInterface::SORT_ORDER_NATURAL_ASC, 'label' => 'Natural sort (A → Z)'],
                            ['value' => BucketInterface::SORT_ORDER_NATURAL_DESC, 'label' => 'Natural sort (Z → A)'],
                        ],
                    ],
                    'gridHeaderInfoTooltip' => 'Non-native OpenSearch sorting may impact performance on facets with many options.<br />Affected sorts are:<br />• Admin sort<br />• Name (Z → A)<br />• Natural sort (A → Z)<br />• Natural sort (Z → A)',
                ],
            ],
        ],
    )]
    #[Groups(['facet_configuration:read', 'facet_configuration:write'])]
    private ?string $sortOrder = null;

    #[ApiProperty(
        extraProperties: [
            'hydra:supportedProperty' => [
                'hydra:property' => [
                    'rdfs:label' => 'Facet recommenders',
                ],
                'gally' => [
                    'visible' => false,
                    'editable' => true,
                    'position' => 70,
                ],
            ],
        ],
    )]
    #[Groups(['facet_configuration:read', 'facet_configuration:write'])]
    private ?bool $isRecommendable = null;

    #[ApiProperty(
        extraProperties: [
            'hydra:supportedProperty' => [
                'hydra:property' => [
                    'rdfs:label' => 'Virtual attributes',
                ],
                'gally' => [
                    'visible' => false,
                    'editable' => true,
                    'position' => 80,
                ],
            ],
        ],
    )]
    #[Groups(['facet_configuration:read', 'facet_configuration:write'])]
    private ?bool $isVirtual = null;

    #[ApiProperty(
        extraProperties: [
            'hydra:supportedProperty' => [
                'hydra:property' => [
                    'rdfs:label' => 'Facet internal logic',
                ],
                'gally' => [
                    'visible' => true,
                    'editable' => true,
                    'position' => 85,
                    'input' => 'select',
                    'options' => [
                        'values' => [
                            ['value' => self::FILTER_LOGICAL_OPERATOR_OR, 'label' => 'Logical OR'],
                            ['value' => self::FILTER_LOGICAL_OPERATOR_AND, 'label' => 'Logical AND'],
                        ],
                    ],
                    'gridHeaderInfoTooltip' => 'When several values are selected in a facet/filter, the default ' .
                        'behavior is to combine them with a logical OR ("red" OR "blue"). But a logical AND can be ' .
                        'handy for some attributes ("egg free" AND "gluten free", "waterproof AND lightweight AND warm").',
                ],
            ],
        ],
    )]
    #[Groups(['facet_configuration:read', 'facet_configuration:write'])]
    private ?string $booleanLogic = null;

    #[ApiProperty(
        extraProperties: [
            'hydra:supportedProperty' => [
                'hydra:property' => [
                    'rdfs:label' => 'Position',
                ],
                'gally' => [
                    'visible' => true,
                    'editable' => true,
                    'position' => 90,
                ],
            ],
        ],
    )]
    #[Groups(['facet_configuration:read', 'facet_configuration:write'])]
    private ?int $position = null;

    #[Groups(['facet_configuration:read'])]
    private ?string $defaultDisplayMode = null;

    #[Groups(['facet_configuration:read'])]
    private ?int $defaultCoverageRate = null;

    #[Groups(['facet_configuration:read'])]
    private ?int $defaultMaxSize = null;

    #[Groups(['facet_configuration:read'])]
    private ?string $defaultSortOrder = null;

    #[Groups(['facet_configuration:read'])]
    private ?bool $defaultIsRecommendable = null;

    #[Groups(['facet_configuration:read'])]
    private ?bool $defaultIsVirtual = null;

    #[Groups(['facet_configuration:read'])]
    private ?int $defaultPosition = null;

    #[Groups(['facet_configuration:read'])]
    private ?string $defaultBooleanLogic = null;

    public function __construct(SourceField $sourceField, ?Category $category)
    {
        $this->sourceField = $sourceField;
        $this->category = $category;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getSourceField(): SourceField
    {
        return $this->sourceField;
    }

    public function setSourceField(SourceField $sourceField): void
    {
        $this->sourceField = $sourceField;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): void
    {
        $this->category = $category;
    }

    public function getDisplayMode(): ?string
    {
        return $this->displayMode ?? $this->getDefaultDisplayMode();
    }

    public function setDisplayMode(?string $displayMode): void
    {
        $this->displayMode = '' == $displayMode ? null : $displayMode;
    }

    public function getCoverageRate(): ?int
    {
        return $this->coverageRate ?? $this->getDefaultCoverageRate();
    }

    public function setCoverageRate(?int $coverageRate): void
    {
        $this->coverageRate = '' == $coverageRate ? null : $coverageRate;
    }

    public function getMaxSize(): ?int
    {
        return $this->maxSize ?? $this->getDefaultMaxSize();
    }

    public function setMaxSize(?int $maxSize): void
    {
        $this->maxSize = $maxSize;
    }

    public function getSortOrder(): ?string
    {
        return $this->sortOrder ?? $this->getDefaultSortOrder();
    }

    public function setSortOrder(?string $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    public function getIsRecommendable(): ?bool
    {
        return $this->isRecommendable ?? $this->getDefaultIsRecommendable();
    }

    public function setIsRecommendable(?bool $isRecommendable): void
    {
        $this->isRecommendable = $isRecommendable;
    }

    public function getIsVirtual(): ?bool
    {
        return $this->isVirtual ?? $this->getDefaultIsVirtual();
    }

    public function setIsVirtual(?bool $isVirtual): void
    {
        $this->isVirtual = $isVirtual;
    }

    public function getPosition(): ?int
    {
        return $this->position ?? $this->getDefaultPosition();
    }

    public function setPosition(?int $position): void
    {
        $this->position = $position;
    }

    public function getBooleanLogic(): ?string
    {
        return $this->booleanLogic ?? $this->getDefaultBooleanLogic();
    }

    public function setBooleanLogic(?string $booleanLogic): void
    {
        $this->booleanLogic = $booleanLogic;
    }

    public function getDefaultDisplayMode(): ?string
    {
        return $this->defaultDisplayMode;
    }

    public function getDefaultCoverageRate(): ?int
    {
        return $this->defaultCoverageRate;
    }

    public function getDefaultMaxSize(): ?int
    {
        return $this->defaultMaxSize;
    }

    public function getDefaultSortOrder(): ?string
    {
        return $this->defaultSortOrder;
    }

    public function getDefaultIsRecommendable(): ?bool
    {
        return $this->defaultIsRecommendable;
    }

    public function getDefaultIsVirtual(): ?bool
    {
        return $this->defaultIsVirtual;
    }

    public function getDefaultPosition(): ?int
    {
        return $this->defaultPosition;
    }

    public function getDefaultBooleanLogic(): ?string
    {
        return $this->defaultBooleanLogic;
    }

    #[ApiProperty(
        extraProperties: [
            'hydra:supportedProperty' => [
                'hydra:property' => [
                    'rdfs:label' => 'Attribute code',
                ],
                'gally' => [
                    'visible' => true,
                    'editable' => false,
                    'position' => 10,
                    'alias' => 'sourceField.code',
                    'sticky' => true,
                ],
            ],
        ],
    )]
    #[Groups(['facet_configuration:read'])]
    public function getSourceFieldCode(): string
    {
        return $this->getSourceField()->getCode();
    }

    #[ApiProperty(
        extraProperties: [
            'hydra:supportedProperty' => [
                'hydra:property' => [
                    'rdfs:label' => 'Attribute label',
                ],
                'gally' => [
                    'visible' => true,
                    'editable' => false,
                    'position' => 20,
                    'alias' => 'sourceField.defaultLabel',
                ],
            ],
        ],
    )]
    #[Groups(['facet_configuration:read'])]
    public function getSourceFieldLabel(): string
    {
        return $this->getSourceField()->getDefaultLabel();
    }

    public function initDefaultValue(self $defaultConfiguration)
    {
        $this->defaultDisplayMode = $defaultConfiguration->getDisplayMode() ?? self::DEFAULT_VALUES['displayMode'];
        $this->defaultCoverageRate = $defaultConfiguration->getCoverageRate() ?? self::DEFAULT_VALUES['coverageRate'];
        $this->defaultMaxSize = $defaultConfiguration->getMaxSize() ?? self::DEFAULT_VALUES['maxSize'];
        $this->defaultSortOrder = $defaultConfiguration->getSortOrder() ?? self::DEFAULT_VALUES['sortOrder'];
        $this->defaultIsRecommendable = $defaultConfiguration->getIsRecommendable() ?? self::DEFAULT_VALUES['isRecommendable'];
        $this->defaultIsVirtual = $defaultConfiguration->getIsVirtual() ?? self::DEFAULT_VALUES['isVirtual'];
        $this->defaultPosition = $defaultConfiguration->getPosition() ?? self::DEFAULT_VALUES['position'];
        $this->defaultBooleanLogic = $defaultConfiguration->getBooleanLogic() ?? self::DEFAULT_VALUES['booleanLogic'];
    }

    public static function getAvailableDisplayModes(): array
    {
        return [
            self::DISPLAY_MODE_AUTO,
            self::DISPLAY_MODE_ALWAYS_DISPLAYED,
            self::DISPLAY_MODE_ALWAYS_HIDDEN,
        ];
    }

    public static function getAvailableSortOrder(): array
    {
        return [
            BucketInterface::SORT_ORDER_COUNT,
            BucketInterface::SORT_ORDER_TERM,
            BucketInterface::SORT_ORDER_TERM_DESC,
            BucketInterface::SORT_ORDER_MANUAL,
            BucketInterface::SORT_ORDER_NATURAL_ASC,
            BucketInterface::SORT_ORDER_NATURAL_DESC,
        ];
    }
}
