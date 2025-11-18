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

use Gally\Metadata\Entity\SourceField;
use Gally\Search\Elasticsearch\Request\BucketInterface;
use Gally\Search\Elasticsearch\Request\ContainerConfigurationInterface;

class SelectAggregationConfigResolver implements FieldAggregationConfigResolverInterface
{
    public function supports(SourceField $sourceField): bool
    {
        return SourceField\Type::TYPE_SELECT === $sourceField->getType();
    }

    public function getConfig(ContainerConfigurationInterface $containerConfig, SourceField $sourceField): array
    {
        return [
            'name' => $sourceField->getCode() . '.value',
            'type' => BucketInterface::TYPE_MULTI_TERMS,
            'additionalFields' => [
                $sourceField->getCode() . '.label',
            ],
        ];
    }
}
