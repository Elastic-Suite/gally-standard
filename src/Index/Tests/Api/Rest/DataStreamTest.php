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

namespace Gally\Index\Tests\Api\Rest;

use Gally\Index\Tests\Api\GraphQl\DataStreamTest as GraphQlDataStreamTest;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestToTest;
use Symfony\Contracts\HttpClient\ResponseInterface;

class DataStreamTest extends GraphQlDataStreamTest
{
    /**
     * @dataProvider createDataStreamDataProvider
     */
    public function testCreateDataStream(
        ?string $role,
        string $entityType,
        string $localizedCatalogCode,
        array $expectedData = []
    ): void {
        $user = $role ? $this->getUser($role) : null;
        $localizedCatalog = self::$localizedCatalogRepository->findOneBy(['code' => $localizedCatalogCode]);

        $this->validateApiCall(
            new RequestToTest(
                'POST',
                'data_streams',
                $user,
                [
                    'entityType' => $entityType,
                    'localizedCatalog' => $localizedCatalog->getCode(),
                ],
            ),
            new ExpectedResponse(
                $expectedData['statusCode'] ?? 201,
                function (ResponseInterface $response) use ($expectedData) {
                    if (isset($expectedData['error'])) {
                        $this->assertGraphQlError($expectedData['error']);
                    } else {
                        $this->assertJsonContains($expectedData);
                    }
                },
                $expectedData['error'] ?? null
            )
        );
    }

    /**
     * @depends testCreateDataStream
     *
     * @dataProvider getDataStreamDataProvider
     */
    public function testGetDataStream(?string $role, string $dataStreamName, ?array $expectedData): void
    {
        $this->validateApiCall(
            new RequestToTest(
                'GET',
                "data_streams/$dataStreamName",
                $role ? $this->getUser($role) : null,
            ),
            new ExpectedResponse(
                $expectedData['statusCode'] ?? 200,
                function (ResponseInterface $response) use ($expectedData) {
                    if (isset($expectedData['error'])) {
                        $this->assertGraphQlError($expectedData['error']);
                    } else {
                        $expectedData['@id'] = $expectedData['id'];
                        unset($expectedData['id']);
                        $this->assertJsonContains($expectedData);
                    }
                },
                $expectedData['error'] ?? null
            )
        );
    }

    /**
     * @depends testGetDataStream
     *
     * @dataProvider getCollectionDataProvider
     */
    public function testGetCollection(?string $role, array $expectedDataStreams): void
    {
        $this->validateApiCall(
            new RequestToTest(
                'GET',
                'data_streams',
                $role ? $this->getUser($role) : null,
            ),
            new ExpectedResponse(
                $expectedDataStreams['statusCode'] ?? 200,
                function (ResponseInterface $response) use ($expectedDataStreams) {
                    $responseArray = $response->toArray();
                    $this->assertGreaterThanOrEqual(\count($expectedDataStreams), $responseArray['hydra:totalItems']);
                },
                $expectedDataStreams['error'] ?? null
            )
        );
    }

    /**
     * @depends testBulkDataStream
     *
     * @dataProvider deleteDataStreamDataProvider
     */
    public function testDeleteDataStream(?string $role, string $dataStreamName, ?array $expectedData): void
    {
        if (404 === ($expectedData['statusCode'] ?? null)) {
            $expectedData['error'] = 'Not Found';
        }

        $this->validateApiCall(
            new RequestToTest(
                'DELETE',
                "data_streams/$dataStreamName",
                $role ? $this->getUser($role) : null
            ),
            new ExpectedResponse(
                $expectedData['statusCode'] ?? 204,
                null,
                $expectedData['error'] ?? null
            )
        );
    }
}
