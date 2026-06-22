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

use Gally\Cache\Service\CacheManagerInterface;
use Gally\Index\Api\IndexSettingsInterface;
use Gally\Index\Event\AfterBulkDeleteIndexEvent;
use Gally\Index\Event\AfterBulkIndexEvent;
use Gally\Index\Event\AfterInstallIndexEvent;
use Gally\Search\Elasticsearch\SpellcheckerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class InvalidateSpellingTypeCache implements EventSubscriberInterface
{
    public function __construct(
        private readonly CacheManagerInterface $cacheManager,
        private readonly IndexSettingsInterface $indexSettings,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AfterBulkIndexEvent::NAME => 'invalidateCache',
            AfterInstallIndexEvent::NAME => 'invalidateCache',
            AfterBulkDeleteIndexEvent::NAME => 'invalidateCache',
        ];
    }

    public function invalidateCache(AfterBulkIndexEvent|AfterInstallIndexEvent|AfterBulkDeleteIndexEvent $event): void
    {
        $index = $event->getIndex();
        if (null !== $index->getEntityType() && $this->indexSettings->isInstalled($index)) {
            $this->cacheManager->clearTags([SpellcheckerInterface::CACHE_TAG_SPELLING_TYPE]);
        }
    }
}
