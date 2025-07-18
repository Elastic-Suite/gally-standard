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

use Gally\Configuration\Service\ConfigurationManager;
use Gally\Metadata\Entity\SourceField;
use Gally\Search\Elasticsearch\Request\BucketInterface;
use Gally\Search\Elasticsearch\Request\ContainerConfigurationInterface;

class DateAggregationConfigResolver implements FieldAggregationConfigResolverInterface
{
    public function __construct(private ConfigurationManager $configurationManager)
    {
    }

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
            'interval' => $this->configurationManager->getScopedConfigValue('gally.search_settings.aggregations.default_date_range_interval'),
            'format' => $this->configurationManager->getScopedConfigValue('gally.search_settings.default_date_field_format'),
        ];
    }
}
