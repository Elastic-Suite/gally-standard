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

namespace Gally\Search\Elasticsearch\Request\Aggregation\ConfigResolver;

use Gally\Configuration\State\ConfigurationProvider;
use Gally\Metadata\Entity\SourceField;
use Gally\Search\Elasticsearch\Request\BucketInterface;
use Gally\Search\Elasticsearch\Request\ContainerConfigurationInterface;
use Gally\Search\Service\SearchContext;

class GeoDistanceAggregationConfigResolver implements FieldAggregationConfigResolverInterface
{
    public function __construct(
        private SearchContext $searchContext,
        private ConfigurationProvider $configurationProvider
    ) {
    }

    public function supports(SourceField $sourceField): bool
    {
        return SourceField\Type::TYPE_LOCATION === $sourceField->getType();
    }

    public function getConfig(ContainerConfigurationInterface $containerConfig, SourceField $sourceField): array
    {
        return [
            'name' => $sourceField->getCode(),
            'type' => BucketInterface::TYPE_GEO_DISTANCE,
            'origin' => $this->searchContext->getReferenceLocation(),
            'unit' => $this->configurationProvider->get('gally.search_settings.default_distance_unit'),
            'ranges' => $this->configurationProvider->get('gally.search_settings.aggregations.default_distance_ranges'),
        ];
    }
}
