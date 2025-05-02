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

namespace Gally\Search\Elasticsearch\Request\Aggregation\Provider;

use Gally\Configuration\State\ConfigurationProvider;
use Gally\Metadata\Entity\SourceField;
use Gally\Metadata\Repository\SourceFieldRepository;
use Gally\Search\Elasticsearch\Request\Aggregation\ConfigResolver\FieldAggregationConfigResolverInterface;
use Gally\Search\Elasticsearch\Request\BucketInterface;
use Gally\Search\Elasticsearch\Request\ContainerConfigurationInterface;

/**
 * Aggregations Provider based on source fields.
 */
class AutocompleteSourceFields implements AggregationProviderInterface
{
    /**
     * @param SourceFieldRepository                     $sourceFieldRepository Source field repository
     * @param ConfigurationProvider                     $configurationProvider Configuration provider
     * @param FieldAggregationConfigResolverInterface[] $aggregationResolvers  Attributes Aggregation Resolver Pool
     */
    public function __construct(
        private SourceFieldRepository $sourceFieldRepository,
        private ConfigurationProvider $configurationProvider,
        private iterable $aggregationResolvers,
    ) {
    }

    public function getAggregations(
        ContainerConfigurationInterface $containerConfig,
        $query = null,
        $filters = [],
        $queryFilters = []
    ): array {
        $sourceFields = $this->sourceFieldRepository->findBy(['isUsedInAutocomplete' => true, 'metadata' => $containerConfig->getMetadata()]);

        return $this->getAggregationsConfig($containerConfig, $sourceFields);
    }

    public function useFacetConfiguration(): bool
    {
        return false;
    }

    /**
     * Get aggregations config.
     *
     * @param SourceField[] $sourceFields Source fields
     */
    private function getAggregationsConfig(ContainerConfigurationInterface $containerConfig, array $sourceFields): array
    {
        $aggregations = [];

        foreach ($sourceFields as $sourceField) {
            $aggregationConfig = $this->getAggregationConfig($sourceField, $containerConfig);
            if (!empty($aggregationConfig) && isset($aggregationConfig['name'])) {
                $aggregations[$aggregationConfig['name']] = $aggregationConfig;
            }
        }

        return $aggregations;
    }

    private function getAggregationConfig(SourceField $sourceField, ContainerConfigurationInterface $containerConfig): array
    {
        $config = [
            'name' => $sourceField->getCode(),
            'type' => BucketInterface::TYPE_TERMS,
        ];

        foreach ($this->aggregationResolvers as $aggregationResolver) {
            if ($aggregationResolver->supports($sourceField)) {
                $config = $aggregationResolver->getConfig($containerConfig, $sourceField);
                break;
            }
        }

        $entity = $containerConfig->getMetadata()->getEntity();
        $config['size'] = $this->configurationProvider->get("gally.autocomplete_settings.{$entity}_attribute.max_size")
            ?? $this->configurationProvider->get('gally.autocomplete_settings.document_attribute.max_size');

        return $config;
    }
}
