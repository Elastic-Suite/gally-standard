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

namespace Gally\Metadata\Tests\Unit;

use Gally\Index\Repository\DataStream\DataStreamRepositoryInterface;
use Gally\Test\AbstractTestCase;
use Gally\Tracker\Entity\TrackingEvent;
use Gally\Tracker\MessageHandler\TrackingEventHandler;

class TrackingEventHandlerTest extends AbstractTestCase
{
    protected TrackingEventHandler $eventHandler;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::loadFixture([
            __DIR__ . '/../fixtures/catalogs.yaml',
            __DIR__ . '/../fixtures/metadata.yaml',
        ]);
    }

    /**
     * @throws \ReflectionException
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (!isset($this->eventHandler)) {
            /** @var DataStreamRepositoryInterface $dataStreamRepoMock */
            $dataStreamRepoMock = self::getMockBuilder(DataStreamRepositoryInterface::class)
                ->disableOriginalConstructor()
                ->getMock();

            $handler = static::getContainer()->get(TrackingEventHandler::class);
            $ref = new \ReflectionClass($handler);
            $property = $ref->getProperty('dataStreamRepository');
            $property->setAccessible(true);
            $property->setValue($handler, $dataStreamRepoMock);

            $this->eventHandler = $handler;
        }
    }

    /**
     * @dataProvider handleIncompleteTrackingEventDataProvider
     */
    public function testHandleIncompleteTrackingEvent(TrackingEvent $event, ?string $expectedErrorMessage = null): void
    {
        $this->expectExceptionMessage($expectedErrorMessage);
        $this->eventHandler->__invoke($event);
    }

    protected function handleIncompleteTrackingEventDataProvider(): iterable
    {
        yield 'Empty event' => [
            new TrackingEvent(),
            'eventType: This value should not be blank.
metadataCode: This value should not be blank.
localizedCatalogCode: This value should not be blank.
sessionUid: This value should not be blank.
sessionVid: This value should not be blank.',
        ];

        yield 'Event with invalide data' => [
            $this->buildEventObject([
                'eventType' => 'fake',
                'metadataCode' => 'fake',
                'localizedCatalogCode' => 'fake',
                'entityCode' => 'fake',
                'sourceEventType' => 'fake',
                'sourceMetadataCode' => 'fake',
                'contextType' => 'fake',
            ]),
            'eventType: The value you selected is not a valid choice.
metadataCode: The metadata for entity "fake" does not exist.
localizedCatalogCode: The localized catalog with code "fake" does not exist.
sourceMetadataCode: The metadata for entity "fake" does not exist.
sourceEventType: The value you selected is not a valid choice.
contextType: The value you selected is not a valid choice.',
        ];

        yield 'Incomplete Category view' => [
            $this->buildEventObject([
                'metadataCode' => 'category',
                'entityCode' => 'fake',
            ]),
            'The field product_list is missing from payload data.',
        ];

        yield 'Incomplete Category view 2' => [
            $this->buildEventObject([
                'metadataCode' => 'category',
                'entityCode' => 'cat_01',
                'payload' => [
                    'product_list' => [
                        'item_count' => 'test',
                        'current_page' => 1,
                        'page_count' => 6,
                        'sort_direction' => 'asc',
                        'filters' => 'test',
                    ],
                ],
            ]),
            'The value of item_count is not integer.
The field sort_order is missing from payload data.
The value of filters is not array.',
        ];

        yield 'Incomplete display products' => [
            $this->buildEventObject([
                'eventType' => 'display',
                'entityCode' => 'fake',
            ]),
            'The field display is missing from payload data.',
        ];

        yield 'Incomplete search result' => [
            $this->buildEventObject([
                'eventType' => 'search',
                'entityCode' => 'skuA',
            ]),
            'entityCode: For search result event, no entity code should be provided.
The field product_list is missing from payload data.
The field search_query is missing from payload data.',
        ];

        yield 'Incomplete order' => [
            $this->buildEventObject([
                'eventType' => 'order',
                'entityCode' => 'fake',
            ]),
            'The field order is missing from payload data.',
        ];
    }

    /**
     * Test dynamic index settings.
     *
     * @dataProvider handleTrackingEventDataProvider
     */
    public function testHandleTrackingEvent(TrackingEvent $event): void
    {
        $this->expectNotToPerformAssertions();
        $this->eventHandler->__invoke($event);
    }

    protected function handleTrackingEventDataProvider(): iterable
    {
        yield 'Category view' => [
            $this->buildEventObject([
                'metadataCode' => 'category',
                'entityCode' => 'cat_14',
                'payload' => [
                    'product_list' => [
                        'item_count' => 72,
                        'current_page' => 1,
                        'page_count' => 6,
                        'sort_order' => 'position',
                        'sort_direction' => 'asc',
                        'filters' => [
                            ['name' => 'fashion_material__value', 'value' => '47'],
                        ],
                    ],
                ],
            ]),
            null,
        ];

        yield 'Display products' => [
            $this->buildEventObject([
                'eventType' => 'display',
                'sourceEventType' => 'view',
                'entityCode' => 'VD01',
                'sourceMetadataCode' => 'category',
                'contextType' => 'category',
                'contextCode' => 'cat_14',
                'payload' => ['display' => ['position' => 1]],
            ]),
            null,
        ];

        yield 'Search result' => [
            $this->buildEventObject([
                'eventType' => 'search',
                'sourceEventType' => 'view',
                'contextType' => 'category',
                'contextCode' => 'cat_14',
                'payload' => [
                    'search_query' => [
                        'is_spellchecked' => true,
                        'query_text' => 'dress',
                    ],
                    'product_list' => [
                        'item_count' => 72,
                        'current_page' => 1,
                        'page_count' => 6,
                        'sort_order' => 'position',
                        'sort_direction' => 'asc',
                        'filters' => [
                            ['name' => 'fashion_material__value', 'value' => '47'],
                        ],
                    ],
                ],
            ]),
            null,
        ];

        yield 'Product view' => [
            $this->buildEventObject([
                'entityCode' => 'skuA',
            ]),
            null,
        ];

        yield 'Add to cart' => [
            $this->buildEventObject([
                'eventType' => 'add_to_cart',
                'entityCode' => 'skuA',
            ]),
            null,
        ];

        yield 'Order' => [
            $this->buildEventObject([
                'eventType' => 'order',
                'entityCode' => 'VD01',
                'payload' => [
                    'order' => [
                        'order_id' => '125',
                        'total' => 52.5,
                        'price' => 12.5,
                        'qty' => 3,
                        'row_total' => 37.5,
                    ],
                ],
            ]),
            null,
        ];
    }

    private function buildEventObject(array $data): TrackingEvent
    {
        $event = new TrackingEvent();
        $event
            ->setEventType($data['eventType'] ?? 'view')
            ->setMetadataCode($data['metadataCode'] ?? 'product')
            ->setLocalizedCatalogCode($data['localizedCatalogCode'] ?? 'b2c_fr')
            ->setEntityCode($data['entityCode'] ?? null)
            ->setSourceEventType($data['sourceEventType'] ?? null)
            ->setSourceMetadataCode($data['sourceMetadataCode'] ?? null)
            ->setContextType($data['contextType'] ?? null)
            ->setContextCode($data['contextCode'] ?? null)
            ->setSessionUid($data['sessionUid'] ?? '2a9c9f2d-0aff-5c1c-b0a8-98cb6460a1d2')
            ->setSessionVid($data['sessionVid'] ?? '55779ebd-9f1f-3ca8-dabf-0d2d83306f32')
            ->setPayload(json_encode($data['payload'] ?? []));

        return $event;
    }
}
