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

namespace Gally\Search\Elasticsearch\Request\Aggregation\ConfigResolver;

use Gally\Metadata\Model\SourceField;
use Gally\Search\Elasticsearch\Request\BucketInterface;
use Gally\Search\Elasticsearch\Request\ContainerConfigurationInterface;

class DateAggregationConfigResolver implements FieldAggregationConfigResolverInterface
{
    public function supports(SourceField $sourceField): bool
    {
        return SourceField\Type::TYPE_DATE === $sourceField->getType();
    }

    public function getConfig(ContainerConfigurationInterface $containerConfig, SourceField $sourceField): array
    {
        return [
            'name' => $sourceField->getCode(),
            'type' => BucketInterface::TYPE_DATE_HISTOGRAM,
            'minDocCount' => 1,
        ];
    }
}
