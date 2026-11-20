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

namespace Gally\Tracker\EventSubscriber;

use Gally\Index\Entity\DataStream;
use Gally\Index\Event\BeforeBulkRequestEvent;
use Gally\Tracker\Service\BehavioralDataIndexRolloverManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EnsureBehavioralDataIndexUpToDateBeforeBulk implements EventSubscriberInterface
{
    public function __construct(
        private readonly BehavioralDataIndexRolloverManager $behavioralDataIndexRolloverManager,
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
        foreach ($event->getRequest()->getTargets() as $target) {
            if ($target instanceof DataStream && 'tracking_event' === $target->getEntityType() && $target->getLocalizedCatalog()) {
                $this->behavioralDataIndexRolloverManager->ensureUpToDate($target->getLocalizedCatalog());
            }
        }
    }
}
