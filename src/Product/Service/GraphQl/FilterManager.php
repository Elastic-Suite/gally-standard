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
}
