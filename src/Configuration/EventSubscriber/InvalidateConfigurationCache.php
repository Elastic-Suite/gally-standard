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

namespace Gally\Configuration\EventSubscriber;

use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Gally\Cache\Service\CacheManagerInterface;
use Gally\Configuration\Entity\Configuration;
use Gally\Configuration\Service\ConfigurationManager;

class InvalidateConfigurationCache
{
    private bool $configurationChanged = false;

    public function __construct(private CacheManagerInterface $cacheManager)
    {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->scheduleCacheClear($args->getObject());
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->scheduleCacheClear($args->getObject());
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $this->scheduleCacheClear($args->getObject());
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if (!$this->configurationChanged) {
            return;
        }

        $this->configurationChanged = false;
        $this->cacheManager->clearTags([ConfigurationManager::CACHE_TAG_GALLY_CONFIGURATION]);
    }

    private function scheduleCacheClear(object $object): void
    {
        if ($object instanceof Configuration) {
            $this->configurationChanged = true;
        }
    }
}
