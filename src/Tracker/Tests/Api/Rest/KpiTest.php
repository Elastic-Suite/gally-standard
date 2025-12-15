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
use Gally\Tracker\Tests\Api\GraphQl\KpiTest as GraphQlKpiTest;
use Symfony\Contracts\HttpClient\ResponseInterface;

class KpiTest extends GraphQlKpiTest
{
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
        $urlParams = http_build_query([
            'localizedCatalog' => $localizedCatalogCode,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
        $this->validateApiCall(
            new RequestToTest('GET', "kpis?$urlParams", $role ? $this->getUser($role) : null),
            new ExpectedResponse(
                $expectedData['statusCode'] ?? 200,
                function (ResponseInterface $response) use ($expectedData) {
                    $this->assertJsonContains(['hydra:member' => [$expectedData]]);
                },
            )
        );
    }
}
