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

namespace Gally\Category\EventSubscriber;

use Gally\Category\Exception\SyncCategoryException;
use Gally\Category\Service\CategoryProductPositionManager;
use Gally\Category\Service\CategorySynchronizer;
use Gally\Index\Event\AfterInstallIndexEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SyncCategoryDataAfterInstall implements EventSubscriberInterface
{
    public function __construct(
        private readonly CategorySynchronizer $synchronizer,
        private readonly CategoryProductPositionManager $categoryProductPositionManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AfterInstallIndexEvent::NAME => 'onAfterInstallIndex',
        ];
    }

    public function onAfterInstallIndex(AfterInstallIndexEvent $event): void
    {
        $index = $event->getIndex();

        if ('category' === $index->getEntityType()) { // Synchronize sql data for category entity
            try {
                $this->synchronizer->synchronize($index);
            } catch (SyncCategoryException) {
                // If sync failed, retry sync once, then log the error.
                $this->synchronizer->synchronize($index);
            }
        }

        if ('product' === $index->getEntityType()) {
            $this->categoryProductPositionManager->reindexPositionsByIndex($index);
        }
    }
}
