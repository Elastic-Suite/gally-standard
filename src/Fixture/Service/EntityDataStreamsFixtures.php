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

namespace Gally\Fixture\Service;

use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Index\Repository\DataStream\DataStreamRepositoryInterface;
use Gally\Metadata\Repository\MetadataRepository;

class EntityDataStreamsFixtures implements EntityDataStreamsFixturesInterface
{
    use GetLocalizedCatalogsTrait;

    public function __construct(
        private MetadataRepository $metadataRepository,
        private LocalizedCatalogRepository $localizedCatalogRepository,
        private DataStreamRepositoryInterface $dataStreamRepository,
    ) {
    }

    public function createEntityElasticsearchDataStreams(string $entityType, int|string|null $localizedCatalogIdentifier = null): void
    {
        $metadata = $this->metadataRepository->findByEntity($entityType, false);
        $localizedCatalogs = $this->getLocalizedCatalogs($localizedCatalogIdentifier);

        foreach ($localizedCatalogs as $localizedCatalog) {
            $dataStream = $this->dataStreamRepository->findByMetadata($metadata, $localizedCatalog);
            if ($dataStream) {
                $this->dataStreamRepository->delete($dataStream->getName());
            }
            $this->dataStreamRepository->createForEntity($metadata, $localizedCatalog);
        }
    }

    public function deleteEntityElasticsearchDataStreams(string $entityType, int|string|null $localizedCatalogIdentifier = null): void
    {
        $metadata = $this->metadataRepository->findByEntity($entityType, false);
        $localizedCatalogs = $this->getLocalizedCatalogs($localizedCatalogIdentifier);

        foreach ($localizedCatalogs as $localizedCatalog) {
            $dataStream = $this->dataStreamRepository->findByMetadata($metadata, $localizedCatalog);
            $this->dataStreamRepository->delete($dataStream->getName());
        }
    }
}
