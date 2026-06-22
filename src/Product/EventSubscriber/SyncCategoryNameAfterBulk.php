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

namespace Gally\Product\EventSubscriber;

use Gally\Index\Event\AfterBulkIndexEvent;
use Gally\Product\Service\CategoryNameUpdater;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SyncCategoryNameAfterBulk implements EventSubscriberInterface
{
    public function __construct(
        private CategoryNameUpdater $categoryNameUpdater,
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

        if (null === $index->getEntityType()) {
            return;
        }

        if ('category' === $index->getEntityType()) {
            // Handle category name change ?
        }

        if ('product' === $index->getEntityType() && $index->getLocalizedCatalog()) {
            // Handle copying category.name to category._name
            $this->categoryNameUpdater->updateCategoryNames($index, $event->getData());
        }
    }
}
