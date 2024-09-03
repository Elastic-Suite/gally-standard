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

use Gally\Metadata\Model\SourceField;
use Gally\Search\Elasticsearch\Request\BucketInterface;
use Gally\Search\Elasticsearch\Request\ContainerConfigurationInterface;
use Gally\Search\Service\SearchContext;

class PriceAggregationConfigResolver implements FieldAggregationConfigResolverInterface
{
    public function __construct(
        private SearchContext $searchContext
    ) {
    }

    public function supports(SourceField $sourceField): bool
    {
        return SourceField\Type::TYPE_PRICE === $sourceField->getType();
    }

    public function getConfig(ContainerConfigurationInterface $containerConfig, SourceField $sourceField): array
    {
        return [
            'name' => $sourceField->getCode() . '.price',
            'type' => BucketInterface::TYPE_HISTOGRAM,
            'nestedFilter' => [$sourceField->getCode() . '.group_id' => $this->searchContext->getPriceGroup()],
            'minDocCount' => 1,
        ];
    }
}
