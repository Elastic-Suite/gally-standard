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

use Gally\Index\Entity\Index;
use Gally\Index\Event\BeforeBulkRequestEvent;
use Gally\Product\Service\CategoryNameUpdater;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AddCategoryNameBeforeBulk implements EventSubscriberInterface
{
    public function __construct(
        private CategoryNameUpdater $categoryNameUpdater,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeBulkRequestEvent::NAME => 'onBeforeBulkRequest',
        ];
    }

    public function onBeforeBulkRequest(BeforeBulkRequestEvent $event): void
    {
        $request = $event->getRequest();

        foreach ($request->getTargets() as $target) {
            if ($target instanceof Index && 'product' === $target->getEntityType() && $target->getLocalizedCatalog()) {
                // Handle copying category.name to category._name
                $documents = $request->getDocuments($target);
                $request->setDocuments($target, $this->categoryNameUpdater->updateCategoryNames($target, $documents));
            }
        }
    }
}
