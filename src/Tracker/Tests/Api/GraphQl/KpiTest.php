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
use Gally\User\Constant\Role;
use Symfony\Contracts\HttpClient\ResponseInterface;

class KpiTest extends AbstractTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::loadFixture([
            __DIR__ . '/../../fixtures/source_field.yaml',
            __DIR__ . '/../../fixtures/catalogs.yaml',
            __DIR__ . '/../../fixtures/metadata.yaml',
        ]);

        self::createEntityElasticsearchDataStream('tracking_event');
        self::loadElasticsearchDocumentFixtures([__DIR__ . '/../../fixtures/tracking_event_documents.json']);
    }

    /**
     * @dataProvider getKpiCollectionDataProvider
     */
    public function testGetKpiCollection(
        ?string $role,
        string $localizedCatalogCode,
        string $startDate,
        string $endDate,
        array $expectedData,
    ): void {
        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                {
                   kpis (
                    localizedCatalog: "$localizedCatalogCode"
                    startDate: "$startDate"
                    endDate: "$endDate"
                  ) {
                    localizedCatalog
                    searchCount
                    categoryViewCount
                    productViewCount
                    addToCartCount
                    orderCount
                    sessionCount
                    visitorCount
                  }
                }
                GQL,
                $role ? $this->getUser($role) : null
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use ($expectedData) {
                    if (\array_key_exists('error', $expectedData)) {
                        $this->assertGraphQlError($expectedData['error']);
                    } else {
                        $this->assertJsonContains(['data' => ['kpis' => [$expectedData]]]);
                    }
                }
            )
        );
    }

    public function getKpiCollectionDataProvider(): iterable
    {
        yield 'anonymous' => [
            null,
            'b2c_en',
            '2026-01-01',
            '2026-01-31',
            [
                'statusCode' => 401,
                'error' => 'Access Denied.',
            ],
        ];

        yield 'no data' => [
            Role::ROLE_CONTRIBUTOR,
            'b2c_en',
            '2024-01-01',
            '2024-01-31',
            [
                'localizedCatalog' => 'b2c_en',
                'searchCount' => 0,
                'categoryViewCount' => 0,
                'productViewCount' => 0,
                'addToCartCount' => 0,
                'orderCount' => 0,
                'sessionCount' => 0,
                'visitorCount' => 0,
            ],
        ];

        yield 'one visitor one session' => [
            Role::ROLE_ADMIN,
            'b2c_en',
            '2025-01-01',
            '2025-01-31',
            [
                'localizedCatalog' => 'b2c_en',
                'searchCount' => 1,
                'categoryViewCount' => 0,
                'productViewCount' => 1,
                'addToCartCount' => 1,
                'orderCount' => 0,
                'sessionCount' => 1,
                'visitorCount' => 1,
            ],
        ];

        yield 'one visitor multiple session' => [
            Role::ROLE_ADMIN,
            'b2c_en',
            '2025-01-01',
            '2025-02-31',
            [
                'localizedCatalog' => 'b2c_en',
                'searchCount' => 1,
                'categoryViewCount' => 1,
                'productViewCount' => 2,
                'addToCartCount' => 2,
                'orderCount' => 1,
                'sessionCount' => 2,
                'visitorCount' => 1,
            ],
        ];

        yield 'multiple visitor multiple session' => [
            Role::ROLE_ADMIN,
            'b2c_en',
            '2025-02-01',
            '2025-03-31',
            [
                'localizedCatalog' => 'b2c_en',
                'searchCount' => 0,
                'categoryViewCount' => 2,
                'productViewCount' => 2,
                'addToCartCount' => 1,
                'orderCount' => 1,
                'sessionCount' => 2,
                'visitorCount' => 2,
            ],
        ];

        yield 'all event' => [
            Role::ROLE_ADMIN,
            'b2c_en',
            '2025-01-01',
            '2025-03-31',
            [
                'localizedCatalog' => 'b2c_en',
                'searchCount' => 1,
                'categoryViewCount' => 2,
                'productViewCount' => 3,
                'addToCartCount' => 2,
                'orderCount' => 1,
                'sessionCount' => 3,
                'visitorCount' => 2,
            ],
        ];

        yield 'other store' => [
            Role::ROLE_ADMIN,
            'b2b_en',
            '2025-01-01',
            '2025-03-31',
            [
                'localizedCatalog' => 'b2b_en',
                'searchCount' => 1,
                'categoryViewCount' => 0,
                'productViewCount' => 0,
                'addToCartCount' => 0,
                'orderCount' => 0,
                'sessionCount' => 1,
                'visitorCount' => 1,
            ],
        ];
    }
}
