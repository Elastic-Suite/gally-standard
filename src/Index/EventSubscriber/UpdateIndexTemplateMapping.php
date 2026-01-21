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

namespace Gally\Index\EventSubscriber;

use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Index\Repository\IndexTemplate\IndexTemplateRepositoryInterface;
use Gally\Index\Service\MetadataManager;
use Gally\Metadata\Entity\SourceField;
use Psr\Log\LoggerInterface;

/**
 * Update index template mapping for data stream entity on source field update.
 */
class UpdateIndexTemplateMapping
{
    private array $metadataToUpdate = [];

    public function __construct(
        private MetadataManager $metadataManager,
        private LocalizedCatalogRepository $localizedCatalogRepository,
        private IndexTemplateRepositoryInterface $indexTemplateRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $sourceField = $args->getObject();
        if (!$sourceField instanceof SourceField) {
            return;
        }

        $metadata = $sourceField->getMetadata();
        if ($metadata->isTimeSeriesData()) {
            $this->metadataToUpdate[$metadata->getEntity()] = $metadata;
        }
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $sourceField = $args->getObject();
        if (!$sourceField instanceof SourceField) {
            return;
        }

        $metadata = $sourceField->getMetadata();
        if ($metadata->isTimeSeriesData()) {
            $this->metadataToUpdate[$metadata->getEntity()] = $metadata;
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        try {
            foreach ($this->metadataToUpdate as $metadata) {
                foreach ($this->localizedCatalogRepository->findAll() as $localizedCatalog) {
                    $indexTemplate = $this->indexTemplateRepository->findByMetadata($metadata, $localizedCatalog);
                    if ($indexTemplate) {
                        $newMapping = $this->metadataManager->getMapping($metadata)->asArray();
                        $indexTemplate->setMappings($newMapping);

                        $this->indexTemplateRepository->update($indexTemplate);
                    }
                }
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception);
        }
    }
}
