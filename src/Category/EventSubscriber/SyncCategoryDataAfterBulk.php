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
use Gally\Index\Api\IndexSettingsInterface;
use Gally\Index\Event\AfterBulkIndexEvent;
use Gally\Index\Repository\Index\IndexRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SyncCategoryDataAfterBulk implements EventSubscriberInterface
{
    public function __construct(
        private readonly CategorySynchronizer $synchronizer,
        private readonly IndexSettingsInterface $indexSettings,
        private readonly IndexRepositoryInterface $indexRepository,
        private readonly CategoryProductPositionManager $categoryProductPositionManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AfterBulkIndexEvent::NAME => 'onAfterBulkIndex',
        ];
    }

    public function onAfterBulkIndex(AfterBulkIndexEvent $event): void
    {
        $index = $event->getIndex();
        $data = $event->getData();

        if (null === $index->getEntityType() || !$this->indexSettings->isInstalled($index)) { // Don't synchronize if index is not installed
            return;
        }

        if ('category' === $index->getEntityType()) { // Synchronize sql data for category entity
            $this->indexRepository->refresh($index->getName()); // Force refresh to avoid missing data
            try {
                $this->synchronizer->synchronize($index, $data);
            } catch (SyncCategoryException) {
                // If sync failed, retry sync once, then log the error.
                $this->synchronizer->synchronize($index, $data);
            }
        }

        if ('product' === $index->getEntityType()) {
            $this->indexRepository->refresh($index->getName()); // Force refresh to avoid missing data
            $this->categoryProductPositionManager->reindexPositionsByIndex(
                $index,
                array_column($data, 'id')
            );
        }
    }
}
