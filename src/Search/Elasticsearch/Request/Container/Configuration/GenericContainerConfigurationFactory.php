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

namespace Gally\Search\Elasticsearch\Request\Container\Configuration;

use Gally\Catalog\Entity\LocalizedCatalog;
use Gally\Index\Api\IndexSettingsInterface;
use Gally\Index\Service\MetadataManager;
use Gally\Metadata\Entity\Metadata;
use Gally\Search\Elasticsearch\Request\Aggregation\Provider\AggregationProviderInterface;
use Gally\Search\Elasticsearch\Request\Container\DefaultSortingOptionProviderInterface;
use Gally\Search\Elasticsearch\Request\Container\RelevanceConfiguration\RelevanceConfigurationFactoryInterface;
use Gally\Search\Elasticsearch\Request\ContainerConfigurationFactoryInterface;
use Gally\Search\Elasticsearch\Request\ContainerConfigurationInterface;

class GenericContainerConfigurationFactory implements ContainerConfigurationFactoryInterface
{
    public function __construct(
        private IndexSettingsInterface $indexSettings,
        private MetadataManager $metadataManager,
        private RelevanceConfigurationFactoryInterface $relevanceConfigurationFactory,
        private AggregationProviderInterface $aggregationProvider,
        private ?DefaultSortingOptionProviderInterface $defaultSortingOptionProvider,
    ) {
    }

    public function create(string $requestType, Metadata $metadata, LocalizedCatalog $localizedCatalog): ContainerConfigurationInterface
    {
        $indexName = $this->indexSettings->getIndexAliasFromIdentifier(
            $metadata->getEntity(),
            $localizedCatalog->getId()
        );
        $mapping = $this->metadataManager->getMapping($metadata);

        return new GenericContainerConfiguration(
            $requestType,
            $localizedCatalog,
            $metadata,
            $indexName,
            $mapping,
            $this->relevanceConfigurationFactory->create($localizedCatalog),
            $this->aggregationProvider,
            $this->defaultSortingOptionProvider,
        );
    }
}
