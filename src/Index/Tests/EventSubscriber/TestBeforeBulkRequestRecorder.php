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

namespace Gally\Index\Tests\EventSubscriber;

use Gally\Index\Event\BeforeBulkRequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TestBeforeBulkRequestRecorder implements EventSubscriberInterface
{
    /** @var string[] */
    public static array $targetNames = [];

    public static function getSubscribedEvents(): array
    {
        return [BeforeBulkRequestEvent::NAME => 'onBeforeBulkRequest'];
    }

    public function onBeforeBulkRequest(BeforeBulkRequestEvent $event): void
    {
        foreach ($event->getRequest()->getTargets() as $target) {
            self::$targetNames[] = $target->getName();
        }
    }
}
