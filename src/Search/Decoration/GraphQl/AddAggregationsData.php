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

namespace Gally\Search\Decoration\GraphQl;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Category\Repository\CategoryConfigurationRepository;
use Gally\Metadata\Entity\SourceField;
use Gally\Metadata\Entity\SourceField\Type;
use Gally\Metadata\Repository\MetadataRepository;
use Gally\Metadata\Repository\SourceFieldRepository;
use Gally\Search\Elasticsearch\Adapter\Common\Response\AggregationInterface;
use Gally\Search\Elasticsearch\Adapter\Common\Response\BucketValueInterface;
use Gally\Search\Elasticsearch\Builder\Response\AggregationBuilder;
use Gally\Search\Elasticsearch\Request\BucketInterface;
use Gally\Search\Elasticsearch\Request\Container\Configuration\ContainerConfigurationProvider;
use Gally\Search\Elasticsearch\Request\ContainerConfigurationInterface;
use Gally\Search\Entity\Document;
use Gally\Search\Repository\Facet\ConfigurationRepository;
use Gally\Search\Service\ReverseSourceFieldProvider;
use Gally\Search\Service\SearchContext;
use Gally\Search\State\Paginator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Add aggregations data in graphql search document response.
 */
class AddAggregationsData implements ProcessorInterface
{
    public const AGGREGATION_TYPE_CHECKBOX = 'checkbox';
    public const AGGREGATION_TYPE_BOOLEAN = 'boolean';
    public const AGGREGATION_TYPE_SLIDER = 'slider';
    public const AGGREGATION_TYPE_CATEGORY = 'category';
    public const AGGREGATION_TYPE_DATE_HISTOGRAM = 'date_histogram';
    public const AGGREGATION_TYPE_HISTOGRAM = 'histogram';

    public function __construct(
        private ProcessorInterface $decorated,
        private MetadataRepository $metadataRepository,
        private ContainerConfigurationProvider $containerConfigurationProvider,
        private LocalizedCatalogRepository $localizedCatalogRepository,
        private ConfigurationRepository $facetConfigRepository,
        private SearchContext $searchContext,
        private ReverseSourceFieldProvider $reverseSourceFieldProvider,
        private CategoryConfigurationRepository $categoryConfigurationRepository,
        private SourceFieldRepository $sourceFieldRepository,
        private TranslatorInterface $translator,
        private iterable $availableFilterTypes,
        private array $searchSettings,
    ) {
    }

    /**
     * @param array<string, mixed>&array{request?: Request, previous_data?: mixed, resource_class?: string, original_data?: mixed, args?: array} $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?array
    {
        $result = $this->decorated->process($data, $operation, $uriVariables, $context);

        if (Document::class === $operation->getClass() || is_subclass_of($operation->getClass(), Document::class)) {
            $metadata = $this->metadataRepository->findByEntity($context['args']['entityType']);
            $localizedCatalog = $this->localizedCatalogRepository->findByCodeOrId($context['args']['localizedCatalog']);
            $containerConfig = $this->containerConfigurationProvider->get($metadata, $localizedCatalog, $context['args']['requestType'] ?? null);
            $currentCategory = $this->searchContext->getCategory();
            $this->facetConfigRepository->setCategoryId($currentCategory?->getId());
            $this->facetConfigRepository->setMetadata($containerConfig->getMetadata());

            /** @var Paginator $data */
            $aggregations = $data->getAggregations();
            if (!empty($aggregations)) {
                $result['aggregations'] = [];
                $sourceFields = [];

                foreach ($aggregations as $aggregation) {
                    if (empty($aggregation->getValues())) {
                        continue;
                    }
                    $sourceFields[$aggregation->getField()] = $this->reverseSourceFieldProvider->getSourceFieldFromFieldName(
                        $aggregation->getField(),
                        $containerConfig->getMetadata()
                    );
                }

                $labels = $this->sourceFieldRepository->getLabelsBySourceFields(
                    $sourceFields,
                    $containerConfig->getLocalizedCatalog()
                );

                foreach ($aggregations as $aggregation) {
                    if (empty($aggregation->getValues())) {
                        continue;
                    }
                    $sourceField = $sourceFields[$aggregation->getField()];
                    $result['aggregations'][] = $this->formatAggregation(
                        $aggregation,
                        $containerConfig,
                        $sourceFields[$aggregation->getField()],
                        $sourceField
                            ? ($labels[$sourceField->getId()]['label'] ?? ucfirst($sourceField->getCode()))
                            : $aggregation->getField()
                    );
                }
            }
        }

        return $result;
    }

    private function formatAggregation(
        AggregationInterface $aggregation,
        ContainerConfigurationInterface $containerConfig,
        ?SourceField $sourceField,
        string $label
    ): array {
        $fieldName = $aggregation->getField();
        if ($sourceField) {
            foreach ($this->availableFilterTypes as $type) {
                if ($type->supports($sourceField)) {
                    $fieldName = $type->getGraphQlFieldName($type->getFilterFieldName($sourceField->getCode()));
                    break;
                }
            }
        }

        $data = [
            'field' => $fieldName,
            'label' => $label,
            'type' => match ($sourceField?->getType()) {
                Type::TYPE_PRICE, Type::TYPE_FLOAT, Type::TYPE_INT => self::AGGREGATION_TYPE_SLIDER,
                Type::TYPE_CATEGORY => self::AGGREGATION_TYPE_CATEGORY,
                Type::TYPE_STOCK, Type::TYPE_BOOLEAN => self::AGGREGATION_TYPE_BOOLEAN,
                Type::TYPE_DATE => self::AGGREGATION_TYPE_DATE_HISTOGRAM,
                Type::TYPE_LOCATION => self::AGGREGATION_TYPE_HISTOGRAM,
                default => self::AGGREGATION_TYPE_CHECKBOX,
            },
            'count' => $aggregation->getCount(),
            'options' => null,
        ];

        if (Type::TYPE_DATE === $sourceField?->getType()) {
            $data['date_format'] = $this->searchSettings['default_date_field_format'];
            $data['date_range_interval'] = $this->searchSettings['aggregations']['default_date_range_interval'];
        }

        $this->formatOptions($aggregation, $sourceField, $containerConfig, $data);

        return $data;
    }

    private function formatOptions(AggregationInterface $aggregation, ?SourceField $sourceField, ContainerConfigurationInterface $containerConfig, array &$data)
    {
        if (!empty($aggregation->getValues())) {
            $data['options'] = [];
            $data['count'] = $aggregation->getCount();
            $data['hasMore'] = false;
        }
        $facetConfigs = $sourceField && $containerConfig->getAggregationProvider()->useFacetConfiguration() ? $this->facetConfigRepository->findOndBySourceField($sourceField) : null;
        $labels = [];

        if (Type::TYPE_CATEGORY === $sourceField->getType()) {
            // Extract categories ids from aggregations options (with result) to hydrate labels from DB
            $categoryIds = array_map(
                fn ($item) => $item->getKey(),
                array_filter($aggregation->getValues(), fn ($item) => $item->getCount())
            );
            $categories = $this->categoryConfigurationRepository->findBy(
                ['category' => $categoryIds, 'localizedCatalog' => $containerConfig->getLocalizedCatalog()]
            );
            // Get the name of all categories in aggregation result
            array_walk(
                $categories,
                function ($categoryConfig) use (&$labels) {
                    $labels[$categoryConfig->getCategory()->getId()] = $categoryConfig->getName();
                }
            );
        }

        foreach ($aggregation->getValues() as $value) {
            if ($value instanceof BucketValueInterface) {
                $key = $value->getKey();

                if (AggregationBuilder::OTHER_DOCS_KEY === $key) {
                    $data['hasMore'] = true;
                    continue;
                }

                if (0 === $value->getCount()) {
                    continue;
                }

                if (Type::TYPE_LOCATION === $sourceField->getType()) {
                    $code = $key; // TODO not sure if I should keep the 10-20 key format or only the "to" value (20)
                    $label = $this->getDistanceRangeLabel($key, $containerConfig);
                } elseif (\is_array($key)) {
                    $code = $key[1];
                    $label = 'None' !== $key[0] ? $key[0] : $key[1];
                } else {
                    $code = $key;
                    $label = $labels[$key] ?? $key;
                }

                $data['options'][] = ['count' => $value->getCount(), 'value' => $code, 'label' => $label];
            }
        }

        // Sort options according to option position.
        if (BucketInterface::SORT_ORDER_MANUAL == $facetConfigs?->getSortOrder()) {
            $sourceFieldOptions = $sourceField->getOptions()->toArray();
            $sourceFieldOptions = array_combine(
                array_map(fn ($option) => $option->getCode(), $sourceFieldOptions),
                $sourceFieldOptions
            );
            $options = $data['options'];
            usort(
                $options,
                fn ($a, $b) => (isset($sourceFieldOptions[$a['value']]) ? $sourceFieldOptions[$a['value']]->getPosition() : 1) - (isset($sourceFieldOptions[$b['value']]) ? $sourceFieldOptions[$b['value']]->getPosition() : 1)
            );

            if (\count($options) > $facetConfigs->getMaxSize()) {
                $options = \array_slice($options, 0, $facetConfigs->getMaxSize());
                $data['hasMore'] = true;
            }
            $data['options'] = $options;
        }
    }

    private function getDistanceRangeLabel(string $key, ContainerConfigurationInterface $containerConfig): string
    {
        $range = explode('-', $key);
        $unit = $this->searchSettings['default_distance_unit'];
        if ('*' === $range[0]) {
            return $this->translator->trans(
                'search.distance_facet.option_to.label',
                ['%distance' => $range[1], '%unit' => $unit],
                'gally_search',
                $containerConfig->getLocalizedCatalog()->getLocale()
            );
        }
        if ('*' === $range[1]) {
            return $this->translator->trans(
                'search.distance_facet.option_from.label',
                ['%distance' => $range[0], '%unit' => $unit],
                'gally_search',
                $containerConfig->getLocalizedCatalog()->getLocale()
            );
        }

        return $this->translator->trans(
            'search.distance_facet.option_fromto.label',
            ['%distanceFrom' => $range[0], '%distanceTo' => $range[1], '%unit' => $unit],
            'gally_search',
            $containerConfig->getLocalizedCatalog()->getLocale()
        );
    }
}
