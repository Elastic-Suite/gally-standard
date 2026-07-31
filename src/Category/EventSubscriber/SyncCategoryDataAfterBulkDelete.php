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
use Gally\Category\Repository\CategoryProductMerchandisingRepository;
use Gally\Category\Service\CategorySynchronizer;
use Gally\Index\Api\IndexSettingsInterface;
use Gally\Index\Event\AfterBulkDeleteIndexEvent;
use Gally\Index\Repository\Index\IndexRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SyncCategoryDataAfterBulkDelete implements EventSubscriberInterface
{
    public function __construct(
        private readonly CategorySynchronizer $synchronizer,
        private readonly IndexSettingsInterface $indexSettings,
        private readonly IndexRepositoryInterface $indexRepository,
        private readonly CategoryProductMerchandisingRepository $categoryProductMerchandisingRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AfterBulkDeleteIndexEvent::NAME => 'onAfterBulkDeleteIndex',
        ];
    }

    public function onAfterBulkDeleteIndex(AfterBulkDeleteIndexEvent $event): void
    {
        $index = $event->getIndex();
        $ids = $event->getIds();

        if (null === $index->getEntityType() || !$this->indexSettings->isInstalled($index)) { // Don't synchronize if index is not installed
            return;
        }

        if ('category' === $index->getEntityType()) { // Synchronize sql data for category entity
            $this->indexRepository->refresh($index->getName()); // Force refresh to avoid missing data
            try {
                $this->synchronizer->synchronize($index);
            } catch (SyncCategoryException) {
                // If sync failed, retry sync once, then log the error.
                $this->synchronizer->synchronize($index);
            }
        }

        if ('product' === $index->getEntityType()) {
            // Todo: For the moment we remove only values in the scope localized catalog, the others scopes will be managed in ticket ESPP-437.
            $this->categoryProductMerchandisingRepository->removeByProductIdAndLocalizedCatalog(
                $ids,
                $index->getLocalizedCatalog()
            );
        }
    }
}
