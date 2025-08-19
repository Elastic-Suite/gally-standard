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

namespace Gally\Catalog\Tests\Api\Rest\Source;

use Gally\Catalog\Tests\Api\GraphQl\Source\LocalizedCatalogGroupOptionTest as GraphQlLocalizedCatalogGroupOptionTest;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestToTest;
use Symfony\Contracts\HttpClient\ResponseInterface;

class LocalizedCatalogGroupOptionTest extends GraphQlLocalizedCatalogGroupOptionTest
{
    /**
     * @dataProvider getCollectionDataProvider
     */
    public function testGetCollection(array $expectedData, ?string $keyToGetOnValue): void
    {
        $this->validateApiCall(
            new RequestToTest('GET', 'localized_catalog_group_options', null),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use ($expectedData) {
                    $this->assertJsonContains(['hydra:member' => $expectedData]);
                }
            )
        );
    }
}
