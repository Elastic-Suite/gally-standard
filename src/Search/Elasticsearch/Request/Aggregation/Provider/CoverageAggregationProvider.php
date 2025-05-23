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

use Gally\Metadata\Entity\SourceField\Type;
use Gally\Search\Elasticsearch\Request\BucketInterface;
use Gally\Search\Elasticsearch\Request\ContainerConfigurationInterface;
use Gally\Search\Elasticsearch\Request\QueryFactory;
use Gally\Search\Elasticsearch\Request\QueryInterface;
use Gally\Search\Service\SearchContext;
use Gally\Search\Service\SearchSettingsProvider;

/**
 * Coverage request aggregation resolver.
 * Returns only data we plan to build coverage on (attribute_set_id, indexed_attributes).
 */
class CoverageAggregationProvider implements AggregationProviderInterface
{
    public function __construct(
        private QueryFactory $queryFactory,
        private SearchSettingsProvider $searchSettings,
        private SearchContext $searchContext
    ) {
    }

    public function getAggregations(
        ContainerConfigurationInterface $containerConfig,
        QueryInterface|string|null $query = null,
        array $filters = [],
        array $queryFilters = []
    ): array {
        if ($this->searchSettings->coverageUseIndexedFieldsProperty()) {
            return [
                ['name' => 'indexed_fields', 'field' => 'indexed_fields.keyword', 'type' => BucketInterface::TYPE_TERMS, 'size' => 0],
            ];
        }

        $sourceFields = $containerConfig->getMetadata()->getFilterableInAggregationSourceFields();
        $queries = [];

        foreach ($sourceFields as $sourceField) {
            if (Type::TYPE_PRICE === $sourceField->getType()) {
                $query = $this->queryFactory->create(
                    QueryInterface::TYPE_TERM,
                    ['field' => $sourceField->getCode() . '.group_id', 'value' => $this->searchContext->getPriceGroup()]
                );
            } else {
                $query = $this->queryFactory->create(
                    QueryInterface::TYPE_EXISTS,
                    ['field' => $sourceField->getCode(), 'name' => $sourceField->getCode()]
                );
            }

            if ($sourceField->getNestedPath() || \in_array($sourceField->getType(), Type::COMPLEX_TYPES, true)) {
                $query = $this->queryFactory->create(
                    QueryInterface::TYPE_NESTED,
                    ['path' => $sourceField->getNestedPath() ?: $sourceField->getCode(), 'query' => $query]
                );
            }

            $queries[$sourceField->getCode()] = $query;
        }

        return [
            'indexed_fields' => [
                'name' => 'indexed_fields',
                'type' => BucketInterface::TYPE_QUERY_GROUP,
                'queries' => $queries,
            ],
        ];
    }

    public function useFacetConfiguration(): bool
    {
        return true;
    }
}
