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

namespace Gally\Tracker\Tests\Unit\EventSubscriber;

use Gally\Catalog\Entity\LocalizedCatalog;
use Gally\Index\Dto\Bulk;
use Gally\Index\Entity\DataStream;
use Gally\Index\Event\BeforeBulkRequestEvent;
use Gally\Tracker\EventSubscriber\EnsureBehavioralDataIndexUpToDateBeforeBulk;
use Gally\Tracker\Service\BehavioralDataIndexRolloverManager;
use PHPUnit\Framework\TestCase;

class EnsureBehavioralDataIndexUpToDateBeforeBulkTest extends TestCase
{
    public function testEnsuresIndexIsUpToDateForTrackingEvent(): void
    {
        $localizedCatalog = new LocalizedCatalog();
        $rolloverManager = $this->createMock(BehavioralDataIndexRolloverManager::class);
        $rolloverManager->expects($this->once())->method('ensureUpToDate')->with($localizedCatalog);

        $this->buildSubscriber($rolloverManager)->onBeforeBulkRequest(
            $this->buildEvent(new DataStream('tracking_event', entityType: 'tracking_event', localizedCatalog: $localizedCatalog))
        );
    }

    public function testDoesNothingForOtherEntityTypes(): void
    {
        $rolloverManager = $this->createMock(BehavioralDataIndexRolloverManager::class);
        $rolloverManager->expects($this->never())->method('ensureUpToDate');

        $this->buildSubscriber($rolloverManager)->onBeforeBulkRequest(
            $this->buildEvent(new DataStream('other', entityType: 'other', localizedCatalog: new LocalizedCatalog()))
        );
    }

    public function testDoesNothingWithoutLocalizedCatalog(): void
    {
        $rolloverManager = $this->createMock(BehavioralDataIndexRolloverManager::class);
        $rolloverManager->expects($this->never())->method('ensureUpToDate');

        $this->buildSubscriber($rolloverManager)->onBeforeBulkRequest(
            $this->buildEvent(new DataStream('tracking_event', entityType: 'tracking_event'))
        );
    }

    private function buildEvent(DataStream $dataStream): BeforeBulkRequestEvent
    {
        return new BeforeBulkRequestEvent((new Bulk\Request())->addDocument($dataStream, 'event_1', []));
    }

    private function buildSubscriber(BehavioralDataIndexRolloverManager $rolloverManager): EnsureBehavioralDataIndexUpToDateBeforeBulk
    {
        return new EnsureBehavioralDataIndexUpToDateBeforeBulk($rolloverManager);
    }
}
