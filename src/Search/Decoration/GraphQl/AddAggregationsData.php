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
use Gally\Configuration\Service\ConfigurationManager;
use Gally\Metadata\Entity\SourceField;
use Gally\Metadata\Entity\SourceField\Type;
use Gally\Metadata\Repository\MetadataRepository;
use Gally\Metadata\Repository\SourceFieldRepository;
use Gally\Search\Elasticsearch\Adapter\Common\Response\AggregationInterface;
use Gally\Search\Elasticsearch\Adapter\Common\Response\BucketValueInterface;
use Gally\Search\Elasticsearch\Builder\Response\AggregationBuilder;
use Gally\Search\Elasticsearch\Request\Container\Configuration\ContainerConfigurationProvider;
use Gally\Search\Elasticsearch\Request\ContainerConfigurationInterface;
use Gally\Search\Entity\Document;
use Gally\Search\Repository\Facet\ConfigurationRepository as FacetConfigurationRepository;
use Gally\Search\Service\ReverseSourceFieldProvider;
use Gally\Search\Service\SearchContext;
use Gally\Search\State\Paginator;
use Symfony\Component\HttpFoundation\Request;

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
        private FacetConfigurationRepository $facetConfigRepository,
        private SearchContext $searchContext,
        private ReverseSourceFieldProvider $reverseSourceFieldProvider,
        private SourceFieldRepository $sourceFieldRepository,
        private ConfigurationManager $configurationManager,
        private iterable $availableFilterTypes,
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
        string $label,
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
            $data['date_format'] = $this->configurationManager->getScopedConfigValue('gally.search_settings.default_date_field_format');
            $data['date_range_interval'] = $this->configurationManager->getScopedConfigValue('gally.search_settings.aggregations.default_date_range_interval');
        }

        if (null === $sourceField) {
            $data['options'] = [];
            $data['hasMore'] = false;

            return $data;
        }

        $data['options'] = $containerConfig->getAggregationProvider()->formatAggregationOptions($aggregation, $sourceField, $containerConfig);
        $data['hasMore'] = $this->hasMoreOptions($aggregation, $data['options']);

        return $data;
    }

    /**
     * True if the raw ES response was itself truncated (sum_other_doc_count bucket), or if the
     * provider returned fewer options than there are non-empty raw buckets (e.g. app-side maxSize
     * truncation for sort orders that must be applied on the full option set before slicing).
     */
    private function hasMoreOptions(AggregationInterface $aggregation, array $formattedOptions): bool
    {
        if (isset($aggregation->getValues()[AggregationBuilder::OTHER_DOCS_KEY])) {
            return true;
        }

        $rawOptionCount = \count(array_filter(
            $aggregation->getValues(),
            fn ($value) => $value instanceof BucketValueInterface && $value->getCount() > 0
        ));

        return \count($formattedOptions) < $rawOptionCount;
    }
}
