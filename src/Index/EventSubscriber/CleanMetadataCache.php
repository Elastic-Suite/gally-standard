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
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Gally\Index\Service\MetadataManager;
use Gally\Metadata\Entity\SourceField;
use Gally\Metadata\Entity\SourceFieldLabel;

class CleanMetadataCache
{
    private bool $invalidateOnFlush = false;

    public function __construct(
        private MetadataManager $metadataManager,
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->cleanMetadataCache($args->getObject());
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->cleanMetadataCache($args->getObject());
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $this->cleanMetadataCache($args->getObject());
    }

    /**
     * The post* lifecycle events are dispatched before the transaction is committed, so a concurrent request
     * can rebuild the cache from the not yet updated data. Invalidate the cache once again after the commit.
     */
    public function postFlush(PostFlushEventArgs $args): void
    {
        if (!$this->invalidateOnFlush) {
            return;
        }

        $this->invalidateOnFlush = false;
        $this->metadataManager->invalidateMappingCache();
    }

    private function cleanMetadataCache(object $entity): void
    {
        if (!$entity instanceof SourceField && !$entity instanceof SourceFieldLabel) {
            return;
        }

        $this->metadataManager->invalidateMappingCache();
        $this->invalidateOnFlush = true;
    }
}
