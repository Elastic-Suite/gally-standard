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

namespace Gally\Search\Elasticsearch\Request\Container\Configuration;

use Gally\Catalog\Model\LocalizedCatalog;
use Gally\Index\Model\Index\MappingInterface;
use Gally\Metadata\Model\Metadata;
use Gally\Search\Elasticsearch\Request\Aggregation\Provider\AggregationProviderInterface;
use Gally\Search\Elasticsearch\Request\Container\DefaultSortingOptionProviderInterface;
use Gally\Search\Elasticsearch\Request\Container\RelevanceConfiguration\RelevanceConfigurationInterface;
use Gally\Search\Elasticsearch\Request\ContainerConfigurationInterface;
use Gally\Search\Elasticsearch\Request\QueryInterface;

class GenericContainerConfiguration implements ContainerConfigurationInterface
{
    public function __construct(
        private string $requestType,
        private LocalizedCatalog $localizedCatalog,
        private Metadata $metadata,
        private string $indexName,
        private MappingInterface $mapping,
        private RelevanceConfigurationInterface $relevanceConfiguration,
        private AggregationProviderInterface $aggregationProvider,
        private ?DefaultSortingOptionProviderInterface $defaultSortingOptionProvider,
    ) {
    }

    public function getName(): string
    {
        return $this->requestType;
    }

    public function getIndexName(): string
    {
        return $this->indexName;
    }

    public function getLabel(): string
    {
        return $this->getName();
    }

    public function getMapping(): MappingInterface
    {
        return $this->mapping;
    }

    public function getRelevanceConfig(): RelevanceConfigurationInterface
    {
        return $this->relevanceConfiguration;
    }

    public function getLocalizedCatalog(): LocalizedCatalog
    {
        return $this->localizedCatalog;
    }

    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }

    public function getFilters(): array
    {
        return [];
    }

    public function getAggregations(QueryInterface|string|null $query = null, array $filters = [], array $queryFilters = []): array
    {
        return $this->aggregationProvider->getAggregations($this, $query, $filters, $queryFilters);
    }

    public function getTrackTotalHits(): int|bool
    {
        return true;
    }

    public function getDefaultSortingOption(): array
    {
        return $this->defaultSortingOptionProvider?->getSortingOption($this) ?: [];
    }

    public function getAggregationProvider(): AggregationProviderInterface
    {
        return $this->aggregationProvider;
    }
}
