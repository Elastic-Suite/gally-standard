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
    public function __construct(
        private MetadataManager $metadataManager,
        private LocalizedCatalogRepository $localizedCatalogRepository,
        private IndexTemplateRepositoryInterface $indexTemplateRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->updateIndexTemplateMapping($args->getObject());
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->updateIndexTemplateMapping($args->getObject());
    }

    private function updateIndexTemplateMapping(object $entity): void
    {
        if (!$entity instanceof SourceField) {
            return;
        }

        if ($entity->getMetadata()->isTimeSeriesData()) {
            try {
                foreach ($this->localizedCatalogRepository->findAll() as $localizedCatalog) {
                    $indexTemplate = $this->indexTemplateRepository->findByMetadata(
                        $entity->getMetadata(),
                        $localizedCatalog
                    );

                    if ($indexTemplate) {
                        $newMapping = $this->metadataManager->getMapping($entity->getMetadata())->asArray();
                        $indexTemplate->setMappings($newMapping);

                        $this->indexTemplateRepository->update($indexTemplate);
                    }
                }
            } catch (\Exception $exception) {
                $this->logger->error($exception);
            }
        }
    }
}
