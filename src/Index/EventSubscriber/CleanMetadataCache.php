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
use Gally\Index\Service\MetadataManager;
use Gally\Metadata\Entity\SourceField;

class CleanMetadataCache
{
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

    private function cleanMetadataCache(object $entity): void
    {
        if (!$entity instanceof SourceField) {
            return;
        }

        $this->metadataManager->cleanLocalCache();
    }
}
