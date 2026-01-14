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

namespace Gally\Tracker\Tests\Api\Rest;

use Gally\Test\ExpectedResponse;
use Gally\Test\RequestToTest;
use Gally\Tracker\Entity\TrackingEvent;
use Gally\Tracker\Tests\Api\GraphQl\TrackingEventTest as GraphQlTrackingEventTest;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Contracts\HttpClient\ResponseInterface;

class TrackingEventTest extends GraphQlTrackingEventTest
{
    /**
     * @dataProvider createTrackingEventDataProvider
     */
    public function testCreateTrackingEvent(
        array $data,
        array $expectedEvents = []
    ): void {
        $this->validateApiCall(
            new RequestToTest(
                'POST',
                'tracking_events',
                null,
                $data
            ),
            new ExpectedResponse(
                201,
                function (ResponseInterface $response) use ($expectedEvents) {
                    /** @var InMemoryTransport $transport */
                    $transport = static::getContainer()->get('messenger.transport.tracking');
                    $messages = $transport->getSent();

                    self::assertCount(\count($expectedEvents), $messages);
                    $createdEvents = [];
                    foreach ($messages as $message) {
                        $event = $message->getMessage();
                        self::assertInstanceOf(TrackingEvent::class, $event);
                        $eventData = $event->toArray();
                        unset($eventData['@timestamp']);
                        unset($eventData['id']);
                        $createdEvents[] = $eventData;
                    }
                    $this->assertSame($expectedEvents, $createdEvents);
                },
            )
        );
    }
}
