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

namespace Gally\Tracker\Tests\Api\GraphQl;

use Gally\Test\AbstractTestCase;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestGraphQlToTest;
use Gally\Tracker\Entity\TrackingEvent;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Contracts\HttpClient\ResponseInterface;

class TrackingEventTest extends AbstractTestCase
{
    /**
     * @dataProvider createTrackingEventDataProvider
     */
    public function testCreateTrackingEvent(
        array $data,
        array $expectedEvents = []
    ): void {
        $strData = '';
        foreach ($data as $key => $value) {
            $value = addslashes($value);
            $strData .= "\n$key: \"$value\"";
        }

        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                mutation {
                    createTrackingEvent(
                        input: { $strData }
                    ) {
                        trackingEvent { id }
                    }
                }
                GQL,
                null
            ),
            new ExpectedResponse(
                200,
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
                }
            )
        );
    }

    public function createTrackingEventDataProvider(): iterable
    {
        yield 'categoryView' => [
            [
                'eventType' => 'view',
                'metadataCode' => 'category',
                'localizedCatalogCode' => 'com_fr',
                'entityCode' => 'cat_14',
                'sessionUid' => '2a9c9f2d-0aff-5c1c-b0a8-98cb6460a1d2',
                'sessionVid' => '55779ebd-9f1f-3ca8-dabf-0d2d83306f32',
                'payload' => '{' .
                    '"product_list": {' .
                        '"item_count": 72,' .
                        '"current_page": 1,' .
                        '"page_count": 6,' .
                        '"sort_order": "position",' .
                        '"sort_direction": "asc",' .
                        '"filters": [' .
                            '{ "name": "fashion_material__value", "value": "47" }' .
                        ']' .
                    '}' .
                '}',
            ],
            [
                [
                    'event_type' => 'view',
                    'metadata_code' => 'category',
                    'localized_catalog_code' => 'com_fr',
                    'entity_code' => 'cat_14',
                    'session' => [
                        'uid' => '2a9c9f2d-0aff-5c1c-b0a8-98cb6460a1d2',
                        'vid' => '55779ebd-9f1f-3ca8-dabf-0d2d83306f32',
                    ],
                    'group_id' => '0',
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
            ],
        ];

        yield 'categoryProductDisplay' => [
            [
                'eventType' => 'display',
                'metadataCode' => 'product',
                'localizedCatalogCode' => 'com_fr',

                'sourceEventType' => 'view',
                'sourceMetadataCode' => 'category',
                'contextType' => 'category',
                'contextCode' => 'cat_14',

                'sessionUid' => '2a9c9f2d-0aff-5c1c-b0a8-98cb6460a1d2',
                'sessionVid' => '55779ebd-9f1f-3ca8-dabf-0d2d83306f32',

                'payload' => '{' .
                    '"items": [' .
                        '{ "entityCode": "VD01", "display": { "position": 1 }},' .
                        '{ "entityCode": "VD07", "display": { "position": 2 }},' .
                        '{ "entityCode": "VD08", "display": { "position": 3 }}' .
                    ']' .
                '}',
            ],
            [
                [
                    'event_type' => 'display',
                    'metadata_code' => 'product',
                    'localized_catalog_code' => 'com_fr',
                    'entity_code' => 'VD01',
                    'source' => [
                        'event_type' => 'view',
                        'metadata_code' => 'category',
                    ],
                    'context' => [
                        'context_type' => 'category',
                        'context_code' => 'cat_14',
                    ],
                    'session' => [
                        'uid' => '2a9c9f2d-0aff-5c1c-b0a8-98cb6460a1d2',
                        'vid' => '55779ebd-9f1f-3ca8-dabf-0d2d83306f32',
                    ],
                    'group_id' => '0',
                    'display' => ['position' => 1],
                ],
                [
                    'event_type' => 'display',
                    'metadata_code' => 'product',
                    'localized_catalog_code' => 'com_fr',
                    'entity_code' => 'VD07',
                    'source' => [
                        'event_type' => 'view',
                        'metadata_code' => 'category',
                    ],
                    'context' => [
                        'context_type' => 'category',
                        'context_code' => 'cat_14',
                    ],
                    'session' => [
                        'uid' => '2a9c9f2d-0aff-5c1c-b0a8-98cb6460a1d2',
                        'vid' => '55779ebd-9f1f-3ca8-dabf-0d2d83306f32',
                    ],
                    'group_id' => '0',
                    'display' => ['position' => 2],
                ],
                [
                    'event_type' => 'display',
                    'metadata_code' => 'product',
                    'localized_catalog_code' => 'com_fr',
                    'entity_code' => 'VD08',
                    'source' => [
                        'event_type' => 'view',
                        'metadata_code' => 'category',
                    ],
                    'context' => [
                        'context_type' => 'category',
                        'context_code' => 'cat_14',
                    ],
                    'session' => [
                        'uid' => '2a9c9f2d-0aff-5c1c-b0a8-98cb6460a1d2',
                        'vid' => '55779ebd-9f1f-3ca8-dabf-0d2d83306f32',
                    ],
                    'group_id' => '0',
                    'display' => ['position' => 3],
                ],
            ],
        ];

        yield 'searchResultView' => [
            [
                'eventType' => 'search',
                'metadataCode' => 'product',
                'localizedCatalogCode' => 'com_fr',
                'sessionUid' => '2a9c9f2d-0aff-5c1c-b0a8-98cb6460a1d2',
                'sessionVid' => '55779ebd-9f1f-3ca8-dabf-0d2d83306f32',
                'payload' => '{' .
                    '"search_query": {' .
                        '"is_spellchecked": false,' .
                        '"query_text": "dress"' .
                    '},' .
                    '"product_list": {' .
                        '"item_count": 72,' .
                        '"current_page": 1,' .
                        '"page_count": 6,' .
                        '"sort_order": "position",' .
                        '"sort_direction": "asc",' .
                        '"filters": [' .
                            '{ "name": "fashion_material__value", "value": "47" }' .
                        ']' .
                    '}' .
                '}',
            ],
            [
                [
                    'event_type' => 'search',
                    'metadata_code' => 'product',
                    'localized_catalog_code' => 'com_fr',
                    'session' => [
                        'uid' => '2a9c9f2d-0aff-5c1c-b0a8-98cb6460a1d2',
                        'vid' => '55779ebd-9f1f-3ca8-dabf-0d2d83306f32',
                    ],
                    'group_id' => '0',
                    'search_query' => [
                        'is_spellchecked' => false,
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
            ],
        ];

        yield 'searchProductDisplay' => [
            [
                'eventType' => 'display',
                'metadataCode' => 'product',
                'localizedCatalogCode' => 'com_fr',

                'sourceEventType' => 'search',
                'sourceMetadataCode' => 'product',
                'contextType' => 'search',
                'contextCode' => 'dress',

                'sessionUid' => '2a9c9f2d-0aff-5c1c-b0a8-98cb6460a1d2',
                'sessionVid' => '55779ebd-9f1f-3ca8-dabf-0d2d83306f32',

                'payload' => '{' .
                    '"items": [' .
                        '{ "entityCode": "VD01", "display": { "position": 1 }},' .
                        '{ "entityCode": "VD07", "display": { "position": 2 }},' .
                        '{ "entityCode": "VD08", "display": { "position": 3 }}' .
                    ']' .
                '}',
            ],
            [
                [
                    'event_type' => 'display',
                    'metadata_code' => 'product',
                    'localized_catalog_code' => 'com_fr',
                    'entity_code' => 'VD01',
                    'source' => [
                        'event_type' => 'search',
                        'metadata_code' => 'product',
                    ],
                    'context' => [
                        'context_type' => 'search',
                        'context_code' => 'dress',
                    ],
                    'session' => [
                        'uid' => '2a9c9f2d-0aff-5c1c-b0a8-98cb6460a1d2',
                        'vid' => '55779ebd-9f1f-3ca8-dabf-0d2d83306f32',
                    ],
                    'group_id' => '0',
                    'display' => ['position' => 1],
                ],
                [
                    'event_type' => 'display',
                    'metadata_code' => 'product',
                    'localized_catalog_code' => 'com_fr',
                    'entity_code' => 'VD07',
                    'source' => [
                        'event_type' => 'search',
                        'metadata_code' => 'product',
                    ],
                    'context' => [
                        'context_type' => 'search',
                        'context_code' => 'dress',
                    ],
                    'session' => [
                        'uid' => '2a9c9f2d-0aff-5c1c-b0a8-98cb6460a1d2',
                        'vid' => '55779ebd-9f1f-3ca8-dabf-0d2d83306f32',
                    ],
                    'group_id' => '0',
                    'display' => ['position' => 2],
                ],
                [
                    'event_type' => 'display',
                    'metadata_code' => 'product',
                    'localized_catalog_code' => 'com_fr',
                    'entity_code' => 'VD08',
                    'source' => [
                        'event_type' => 'search',
                        'metadata_code' => 'product',
                    ],
                    'context' => [
                        'context_type' => 'search',
                        'context_code' => 'dress',
                    ],
                    'session' => [
                        'uid' => '2a9c9f2d-0aff-5c1c-b0a8-98cb6460a1d2',
                        'vid' => '55779ebd-9f1f-3ca8-dabf-0d2d83306f32',
                    ],
                    'group_id' => '0',
                    'display' => ['position' => 3],
                ],
            ],
        ];

        yield 'productView' => [
            [
                'eventType' => 'view',
                'metadataCode' => 'product',
                'localizedCatalogCode' => 'com_fr',
                'entityCode' => 'VD01',
                'sourceEventType' => 'search',
                'sourceMetadataCode' => 'product',
                'contextType' => 'search',
                'contextCode' => 'dress',
                'sessionUid' => '2a9c9f2d-0aff-5c1c-b0a8-98cb6460a1d2',
                'sessionVid' => '55779ebd-9f1f-3ca8-dabf-0d2d83306f32',
            ],
            [
                [
                    'event_type' => 'view',
                    'metadata_code' => 'product',
                    'localized_catalog_code' => 'com_fr',
                    'entity_code' => 'VD01',
                    'source' => [
                        'event_type' => 'search',
                        'metadata_code' => 'product',
                    ],
                    'context' => [
                        'context_type' => 'search',
                        'context_code' => 'dress',
                    ],
                    'session' => [
                        'uid' => '2a9c9f2d-0aff-5c1c-b0a8-98cb6460a1d2',
                        'vid' => '55779ebd-9f1f-3ca8-dabf-0d2d83306f32',
                    ],
                    'group_id' => '0',
                ],
            ],
        ];

        yield 'addToCartProduct' => [
            [
                'eventType' => 'add_to_cart',
                'metadataCode' => 'product',
                'localizedCatalogCode' => 'com_fr',
                'entityCode' => 'VD01',
                'sourceEventType' => 'view',
                'sourceMetadataCode' => 'product',
                'contextType' => 'search',
                'contextCode' => 'dress',
                'sessionUid' => '2a9c9f2d-0aff-5c1c-b0a8-98cb6460a1d2',
                'sessionVid' => '55779ebd-9f1f-3ca8-dabf-0d2d83306f32',
                'payload' => '{' .
                    '"cart": {"qty": 2}' .
                '}',
            ],
            [
                [
                    'event_type' => 'add_to_cart',
                    'metadata_code' => 'product',
                    'localized_catalog_code' => 'com_fr',
                    'entity_code' => 'VD01',
                    'source' => [
                        'event_type' => 'view',
                        'metadata_code' => 'product',
                    ],
                    'context' => [
                        'context_type' => 'search',
                        'context_code' => 'dress',
                    ],
                    'session' => [
                        'uid' => '2a9c9f2d-0aff-5c1c-b0a8-98cb6460a1d2',
                        'vid' => '55779ebd-9f1f-3ca8-dabf-0d2d83306f32',
                    ],
                    'group_id' => '0',
                    'cart' => [
                        'qty' => 2,
                    ],
                ],
            ],
        ];

        yield 'orderProduct' => [
            [
                'eventType' => 'order',
                'metadataCode' => 'product',
                'localizedCatalogCode' => 'com_fr',

                'sourceEventType' => 'view',
                'sourceMetadataCode' => 'product',
                'contextType' => 'search',
                'contextCode' => 'dress',

                'sessionUid' => '2a9c9f2d-0aff-5c1c-b0a8-98cb6460a1d2',
                'sessionVid' => '55779ebd-9f1f-3ca8-dabf-0d2d83306f32',

                'payload' => '{' .
                    '"order": {' .
                    '    "order_id": "125",' .
                    '    "total": 52.5' .
                    '},' .
                    '"items": [' .
                    '    {' .
                    '        "entityCode": "VD01",' .
                    '        "order": {' .
                    '            "price": 12.5,' .
                    '            "qty": 3,' .
                    '            "row_total": 37.5' .
                    '        }' .
                    '    },' .
                    '    {' .
                    '        "entityCode": "VD06",' .
                    '        "order": {' .
                    '            "price": 15,' .
                    '            "qty": 1,' .
                    '            "row_total": 15' .
                    '        }' .
                    '    }' .
                    ']' .
                '}',
            ],
            [
                [
                    'event_type' => 'order',
                    'metadata_code' => 'product',
                    'localized_catalog_code' => 'com_fr',
                    'entity_code' => 'VD01',
                    'source' => [
                        'event_type' => 'view',
                        'metadata_code' => 'product',
                    ],
                    'context' => [
                        'context_type' => 'search',
                        'context_code' => 'dress',
                    ],
                    'session' => [
                        'uid' => '2a9c9f2d-0aff-5c1c-b0a8-98cb6460a1d2',
                        'vid' => '55779ebd-9f1f-3ca8-dabf-0d2d83306f32',
                    ],
                    'group_id' => '0',
                    'order' => [
                        'order_id' => '125',
                        'total' => 52.5,
                        'price' => 12.5,
                        'qty' => 3,
                        'row_total' => 37.5,
                    ],
                ],
                [
                    'event_type' => 'order',
                    'metadata_code' => 'product',
                    'localized_catalog_code' => 'com_fr',
                    'entity_code' => 'VD06',
                    'source' => [
                        'event_type' => 'view',
                        'metadata_code' => 'product',
                    ],
                    'context' => [
                        'context_type' => 'search',
                        'context_code' => 'dress',
                    ],
                    'session' => [
                        'uid' => '2a9c9f2d-0aff-5c1c-b0a8-98cb6460a1d2',
                        'vid' => '55779ebd-9f1f-3ca8-dabf-0d2d83306f32',
                    ],
                    'group_id' => '0',
                    'order' => [
                        'order_id' => '125',
                        'total' => 52.5,
                        'price' => 15,
                        'qty' => 1,
                        'row_total' => 15,
                    ],
                ],
            ],
        ];
    }
}
