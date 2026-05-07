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

namespace Gally\Search\EventSubscriber;

use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Gally\Cache\Service\CacheManagerInterface;
use Gally\Search\Elasticsearch\Request\Aggregation\Provider\FilterableSourceFields;
use Gally\Search\Entity\Facet\Configuration;

class InvalidateFacetConfigurationCache
{
    public function __construct(
        private CacheManagerInterface $cacheManager,
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->invalidate($args->getObject());
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->invalidate($args->getObject());
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $this->invalidate($args->getObject());
    }

    private function invalidate(object $entity): void
    {
        if (!$entity instanceof Configuration) {
            return;
        }

        $this->cacheManager->clearTags([FilterableSourceFields::CACHE_TAG_FACET_CONFIG]);
    }
}
