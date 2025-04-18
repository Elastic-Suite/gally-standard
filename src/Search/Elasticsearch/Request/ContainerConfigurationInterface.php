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

namespace Gally\Search\Elasticsearch\Request;

use Gally\Catalog\Entity\LocalizedCatalog;
use Gally\Index\Entity\Index\MappingInterface;
use Gally\Metadata\Entity\Metadata;
use Gally\Search\Elasticsearch\Request\Aggregation\Provider\AggregationProviderInterface;
use Gally\Search\Elasticsearch\Request\Container\RelevanceConfiguration\RelevanceConfigurationInterface;

/**
 * Search request container configuration interface.
 */
interface ContainerConfigurationInterface
{
    /**
     * Search request container name.
     */
    public function getName(): string;

    /**
     * Search request container index name.
     */
    public function getIndexName(): string;

    /**
     * Search request container label.
     */
    public function getLabel(): string;

    /**
     * Search request container mapping.
     */
    public function getMapping(): MappingInterface;

    /**
     * Retrieve the fulltext search relevance configuration for the container.
     */
    public function getRelevanceConfig(): RelevanceConfigurationInterface;

    /**
     * Current container localized catalog.
     */
    public function getLocalizedCatalog(): LocalizedCatalog;

    /**
     * Current metadata.
     */
    public function getMetadata(): Metadata;

    /**
     * Retrieve filters for the container (visibility, in stock, etc ...) and the current search Context.
     *
     * @return QueryInterface[]
     */
    public function getFilters(): array;

    /**
     * Get aggregations configured in the search container.
     *
     * @param string|QueryInterface|null $query        Search request query
     * @param QueryInterface[]           $filters      Search request filters
     * @param QueryInterface[]           $queryFilters Search request filters prebuilt as QueryInterface
     */
    public function getAggregations(string|QueryInterface|null $query = null, array $filters = [], array $queryFilters = []): array;

    /**
     * Get the value of the track_total_hits parameter, if any.
     */
    public function getTrackTotalHits(): int|bool;

    /**
     * Get default sorting option for context.
     */
    public function getDefaultSortingOption(): array;

    /**
     * Get aggregation provider.
     */
    public function getAggregationProvider(): AggregationProviderInterface;
}
