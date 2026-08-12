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

use Gally\Metadata\Entity\SourceField;
use Gally\Search\Elasticsearch\Adapter\Common\Response\AggregationInterface;
use Gally\Search\Elasticsearch\Request\ContainerConfigurationInterface;
use Gally\Search\Service\AggregationOptionsFormatter;

/**
 * Default Aggregations Provider for Search Requests.
 */
class GenericAggregationProvider implements AggregationProviderInterface
{
    public function __construct(
        private AggregationOptionsFormatter $aggregationOptionsFormatter,
    ) {
    }

    public function getAggregations(
        ContainerConfigurationInterface $containerConfig,
        $query = null,
        $filters = [],
        $queryFilters = [],
    ): array {
        return [];
    }

    public function formatAggregationOptions(
        AggregationInterface $aggregation,
        SourceField $sourceField,
        ContainerConfigurationInterface $containerConfig,
    ): array {
        return $this->aggregationOptionsFormatter->format($aggregation, $sourceField, $containerConfig);
    }
}
