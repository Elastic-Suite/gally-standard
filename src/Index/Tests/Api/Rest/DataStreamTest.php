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
use Gally\Test\RequestGraphQlToTest;
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
        $user = $role ? $this->getUser($role) : null;

        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    query {
                      dataStream(id: "$dataStreamName") {
                        id
                        name
                        status
                        indices { name status }
                      }
                    }
                GQL,
                $user,
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use ($expectedData) {
                    if (isset($expectedData['error'])) {
                        $this->assertGraphQlError($expectedData['error']);
                    } else {
                        $this->assertJsonContains(['data' => ['dataStream' => $expectedData]]);
                    }
                }
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
        $user = $role ? $this->getUser($role) : null;

        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    query {
                      dataStreams {
                        id
                        status
                      }
                    }
                GQL,
                $user,
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use ($expectedDataStreams) {
                    if (isset($expectedDataStreams['error'])) {
                        $this->assertGraphQlError($expectedDataStreams['error']);
                    } else {
                        $responseArray = $response->toArray();
                        $this->assertGreaterThanOrEqual(\count($expectedDataStreams), $responseArray['data']['dataStreams']);
                    }
                }
            )
        );
    }

    /**
     * @depends testGetCollection
     *
     * @dataProvider bulkDataStreamDataProvider
     */
    public function testBulkDataStream(?string $role, string $dataStreamName, array $documents, array $expectedData): void
    {
        $user = $role ? $this->getUser($role) : null;
        $documentsAsString = addslashes(json_encode($documents));

        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    mutation {
                      bulkDataStream(input: {
                        name: "$dataStreamName",
                        data: "$documentsAsString"
                      }) {
                        dataStream { name }
                      }
                    }
                GQL,
                $user,
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use ($expectedData) {
                    if (\array_key_exists('error', $expectedData)) {
                        $this->assertGraphQlError($expectedData['error']);
                    } else {
                        $this->assertJsonContains(['data' => ['bulkDataStream' => $expectedData]]);
                    }
                }
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
        $user = $role ? $this->getUser($role) : null;

        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    mutation {
                      deleteDataStream(input: {
                        id: "$dataStreamName"
                      }) {
                        dataStream { id }
                      }
                    }
                GQL,
                $user,
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use ($expectedData) {
                    if (isset($expectedData['error'])) {
                        $this->assertGraphQlError($expectedData['error']);
                    } else {
                        $responseArray = $response->toArray();
                        $this->assertNull($responseArray['data']['deleteDataStream']['dataStream']);
                    }
                }
            )
        );
    }
}
