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

namespace Gally\Product\Tests\Api\Rest;

use Gally\Product\Tests\Api\GraphQl\ProductSourceFieldLabelTest as GraphQlProductSourceFieldLabelTest;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestToTest;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ProductSourceFieldLabelTest extends GraphQlProductSourceFieldLabelTest
{
    /**
     * @dataProvider getLabelsDataProvider
     *
     * @param string[]                                       $codes
     * @param array<int, array{code: string, label: string}> $expectedData
     */
    public function testGetCollection(array $codes, string $localizedCatalog, array $expectedData): void
    {
        // No user is passed, so no bearer token is sent: the endpoint must answer anonymously.
        $this->validateApiCall(
            new RequestToTest('GET', $this->buildPath($codes, $localizedCatalog), null),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use ($expectedData) {
                    $this->assertJsonContains(['hydra:member' => $expectedData]);
                }
            )
        );
    }

    public function testMissingCodes(): void
    {
        $this->validateApiCall(
            new RequestToTest('GET', 'product_source_field_labels?localizedCatalog=b2c_fr', null),
            new ExpectedResponse(400, null, 'The codes argument is required and cannot be empty.')
        );
    }

    public function testMissingLocalizedCatalog(): void
    {
        $this->validateApiCall(
            new RequestToTest('GET', 'product_source_field_labels?codes[]=size', null),
            new ExpectedResponse(400, null, 'The localizedCatalog argument is required.')
        );
    }

    public function testUnknownLocalizedCatalog(): void
    {
        $this->validateApiCall(
            new RequestToTest('GET', $this->buildPath(['size'], 'not_a_catalog'), null),
            new ExpectedResponse(400, null, 'Missing localized catalog [not_a_catalog]')
        );
    }

    /**
     * There is no item operation, so a single code cannot be probed through a dedicated route.
     */
    public function testNoCollectionWithoutCodes(): void
    {
        $response = $this->request(new RequestToTest('GET', 'product_source_field_labels/size', null));

        $this->assertContains($response->getStatusCode(), [404, 405]);
    }

    /**
     * @param string[] $codes
     */
    private function buildPath(array $codes, string $localizedCatalog): string
    {
        $query = http_build_query(['codes' => $codes, 'localizedCatalog' => $localizedCatalog]);

        return 'product_source_field_labels?' . $query;
    }
}
