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

namespace Gally\Product\Service\GraphQl;

use Gally\Search\Elasticsearch\Request\ContainerConfigurationInterface;
use Gally\Search\GraphQl\Type\Definition\FieldFilterInputType;
use Gally\Search\Service\SearchContext;

class FilterManager extends \Gally\Search\Service\GraphQl\FilterManager
{
    public function __construct(
        private FieldFilterInputType $fieldFilterInputType,
        protected string $nestingSeparator,
        private SearchContext $searchContext,
    ) {
        parent::__construct($fieldFilterInputType, $nestingSeparator);
    }

    public function getQueryFilterFromContext(array $context): array
    {
        $queryFilters = parent::getQueryFilterFromContext($context);

        if ($this->searchContext->getCategory()) {
            $queryFilters[]['category__id'] = ['eq' => $this->searchContext->getCategory()->getId()];
        }

        return $queryFilters;
    }

    public function transformToGallyFilters(array $graphQlFilters, ContainerConfigurationInterface $containerConfig, array $filterContext = []): array
    {
        $esFilters = [];
        foreach ($graphQlFilters as $filters) {
            $filters = $this->groupFiltersByPrefix($filters);
            foreach ($filters as $sourceFieldName => $condition) {
                if (str_contains($sourceFieldName, '.')) {
                    // Api platform automatically replace nesting separator by '.',
                    // but it keeps the value with nesting separator. In order to avoid applying
                    // the filter twice, we have to skip the one with the '.'.
                    continue;
                }
                $esFilterData = $this->fieldFilterInputType->transformToGallyFilter(
                    [$sourceFieldName => $condition],
                    $containerConfig,
                    $filterContext
                );

                if ('boolFilter' == $sourceFieldName) {
                    $esFilters[] = $esFilterData;
                } else {
                    $esFilters[str_replace($this->nestingSeparator, '.', $sourceFieldName)] = $esFilterData;
                }
            }
        }

        return $esFilters;
    }

    /**
     * Group filters sharing the same prefix (before '__' or '.') into a scopedFilter.
     *
     * Fields like 'stock__is_in_stock' and 'stock__qty' share the prefix 'stock'.
     * When two or more distinct fields share the same prefix, they are merged under:
     *   ['scopedFilter' => ['_must' => [['stock__is_in_stock' => ...], ['stock__qty' => ...]]]]
     * Fields with a unique prefix or no separator are left unchanged.
     *
     * Note: 'price.price' and 'price__price' are considered the same field
     * (API Platform automatically replaces the nesting separator with '.'),
     * so they count as a single occurrence and are never grouped.
     */
    private function groupFiltersByPrefix(array $filters): array
    {
        $byPrefix = [];

        foreach ($filters as $sourceFieldName => $condition) {
            // Extract the prefix: the part before the nesting separator or '.'
            if (str_contains($sourceFieldName, $this->nestingSeparator)) {
                $prefix = strstr($sourceFieldName, $this->nestingSeparator, true);
            } elseif (str_contains($sourceFieldName, '.')) {
                $prefix = strstr($sourceFieldName, '.', true);
            } else {
                $prefix = null;
            }

            $byPrefix[$prefix ?? $sourceFieldName][$sourceFieldName] = $condition;
        }

        $result = [];

        foreach ($byPrefix as $group) {
            // Deduplicate entries that are the same field expressed with '.' or '__'.
            // e.g. 'price.price' and 'price__price' normalize to the same key.
            $seen = [];
            $uniqueGroup = [];
            foreach ($group as $fieldName => $condition) {
                $normalizedName = str_replace('.', $this->nestingSeparator, $fieldName);
                if (!\array_key_exists($normalizedName, $seen)) {
                    $seen[$normalizedName] = true;
                    $uniqueGroup[$fieldName] = $condition;
                }
            }

            if (\count($uniqueGroup) >= 2) {
                // Multiple distinct fields share the same prefix: wrap them in a scopedFilter.
                // _must expects a list of FieldFilterInput, each being an associative array of
                // sourceFieldName => condition. All fields of the group go into ONE single entry
                // so that FieldFilterInputType::transformToGallyFilter can iterate over all of them.
                $mustEntry = $uniqueGroup; // ['stock__is_in_stock' => ..., 'stock__qty' => ...]
                // scopedFilter may already exist (from a previous prefix group), so append.
                if (isset($result['scopedFilter'])) {
                    $result['scopedFilter']['_must'][] = $mustEntry;
                } else {
                    $result['scopedFilter'] = ['_must' => [$mustEntry]];
                }
            } else {
                // Single distinct field (or no prefix): keep as-is.
                foreach ($group as $fieldName => $condition) {
                    $result[$fieldName] = $condition;
                }
            }
        }

        return $result;
    }
}
