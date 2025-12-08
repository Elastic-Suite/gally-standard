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

namespace Gally\Index\Tests\Api\GraphQl;

use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Index\Repository\DataStream\DataStreamRepositoryInterface;
use Gally\Index\Repository\IndexTemplate\IndexTemplateRepositoryInterface;
use Gally\Test\AbstractTestCase;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestGraphQlToTest;
use Gally\User\Constant\Role;
use OpenSearch\Client;
use Symfony\Contracts\HttpClient\ResponseInterface;

class DataStreamTest extends AbstractTestCase
{
    protected static DataStreamRepositoryInterface $dataStreamRepository;
    protected static LocalizedCatalogRepository $localizedCatalogRepository;
    protected static Client $client;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::loadFixture([
            __DIR__ . '/../../fixtures/catalogs.yaml',
            __DIR__ . '/../../fixtures/source_field.yaml',
            __DIR__ . '/../../fixtures/metadata.yaml',
        ]);

        self::$dataStreamRepository = static::getContainer()->get(DataStreamRepositoryInterface::class);
        self::$localizedCatalogRepository = static::getContainer()->get(LocalizedCatalogRepository::class);
        self::$client = static::getContainer()->get('opensearch.client.test');

        self::cleanupTestDataStreams();
    }

    public static function tearDownAfterClass(): void
    {
        self::cleanupTestDataStreams();
        parent::tearDownAfterClass();
    }

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

        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    mutation {
                      createDataStream(input: {
                        entityType: "{$entityType}",
                        localizedCatalog: "{$localizedCatalogCode}"
                      }) {
                        dataStream {
                          name
                        }
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
                        $this->assertJsonContains(['data' => ['createDataStream' => ['dataStream' => $expectedData]]]);
                    }
                }
            )
        );
    }

    public function createDataStreamDataProvider(): iterable
    {
        yield 'anonymous' => [null, 'product', 'b2c_en', ['error' => 'Access Denied.', 'statusCode' => 401]];
        yield 'contributor' => [Role::ROLE_CONTRIBUTOR, 'product', 'b2c_en', ['error' => 'Access Denied.', 'statusCode' => 403]];
        yield 'product datastream' => [
            Role::ROLE_ADMIN,
            'product',
            'b2c_fr',
            ['error' => 'Cannot create data stream for non-time-series entity', 'statusCode' => 500],
        ];

        yield 'event datastream' => [
            Role::ROLE_ADMIN,
            'event',
            'b2c_en',
            ['name' => 'gally_test__gally_b2c_en_event'],
        ];
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

    public function getDataStreamDataProvider(): iterable
    {
        yield 'anonymous' => [null, '/api/data_streams/gally_test__gally_b2c_en_event', ['error' => 'Access Denied.']];
        yield 'missing' => [Role::ROLE_CONTRIBUTOR, '/api/data_streams/missing_data_stream', null];
        yield 'contributor' => [
            Role::ROLE_CONTRIBUTOR,
            '/api/data_streams/gally_test__gally_b2c_en_event',
            [
                'id' => '/api/data_streams/gally_test__gally_b2c_en_event',
                'name' => 'gally_test__gally_b2c_en_event',
                'status' => 'GREEN',
            ],
        ];
        yield 'admin' => [
            Role::ROLE_ADMIN,
            '/api/data_streams/gally_test__gally_b2c_en_event',
            [
                'id' => '/api/data_streams/gally_test__gally_b2c_en_event',
                'name' => 'gally_test__gally_b2c_en_event',
                'status' => 'GREEN',
            ],
        ];
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

    public function getCollectionDataProvider(): iterable
    {
        yield 'anonymous' => [null, ['error' => 'Access Denied.']];
        yield 'contributor' => [Role::ROLE_CONTRIBUTOR, ['/api/data_streams/gally_test__gally_b2c_en_event']];
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

    public function bulkDataStreamDataProvider(): iterable
    {
        yield 'anonymous' => [null, '', [], ['error' => 'Access Denied.']];
        yield 'contributor' => [Role::ROLE_CONTRIBUTOR, '', [], ['error' => 'Access Denied.']];
        yield 'bulk missing datastream ' => [
            Role::ROLE_ADMIN,
            'gally_test__gally_b2c_en_event',
            [
                ['event_type' => 'view', '@timestamp' => date('Y-m-d H:i:s')],
                ['event_type' => 'order', '@timestamp' => date('Y-m-d H:i:s')],
            ],
            ['dataStream' => ['name' => 'gally_test__gally_b2c_en_event']],
        ];
        yield 'bulk event data' => [
            Role::ROLE_ADMIN,
            'gally_test__gally_b2c_en_event',
            [
                ['event_type' => 'view', '@timestamp' => date('Y-m-d H:i:s')],
                ['event_type' => 'order', '@timestamp' => date('Y-m-d H:i:s')],
            ],
            ['dataStream' => ['name' => 'gally_test__gally_b2c_en_event']],
        ];
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

    public function deleteDataStreamDataProvider(): iterable
    {
        yield 'anonymous' => [null, '/api/data_streams/gally_test__gally_b2c_en_event', ['error' => 'Access Denied.']];
        yield 'contributor' => [Role::ROLE_CONTRIBUTOR, '/api/data_streams/gally_test__gally_b2c_en_event', ['error' => 'Access Denied.']];
        yield 'missing' => [Role::ROLE_ADMIN, '/api/data_streams/missing_data_stream', ['error' => 'Item "/api/data_streams/missing_data_stream" not found.']];
        yield 'admin' => [
            Role::ROLE_ADMIN,
            '/api/data_streams/gally_test__gally_b2c_en_event',
            [
                'id' => '/api/data_streams/gally_test__gally_b2c_en_event',
                'name' => 'gally_test__gally_b2c_en_event',
                'status' => 'GREEN',
            ],
        ];
    }

    protected static function cleanupTestDataStreams(): void
    {
        // Todo use prefix from config
        // Todo Clean ism and template from data stream repo
        try {
            $response = self::$client->indices()->getDataStream();
            foreach ($response['data_streams'] as $dataStream) {
                if (str_contains($dataStream['name'], 'gally_test__gally')) {
                    self::$dataStreamRepository->delete($dataStream['name']);
                }
            }
        } catch (\Exception $e) {
            // Ignore errors during cleanup
        }

        try {
            $indexTemplateRepository = static::getContainer()->get(IndexTemplateRepositoryInterface::class);
            $templates = $indexTemplateRepository->findAll();
            foreach ($templates as $template) {
                if (str_contains($template->getId(), 'gally_test__gally')) {
                    $indexTemplateRepository->delete($template->getId());
                }
            }
        } catch (\Exception $e) {
            // Ignore errors during cleanup
        }

        //        try {
        //            // Todo manage ism deleteion
        //            $ismRepository = static::getContainer()->get(IndexStateManagementRepositoryInterface::class);
        //            $policies = $ismRepository->findAll();
        //            foreach ($policies as $policy) {
        //                if (str_contains($policy->getName(), 'gally_test_')) {
        //                    $ismRepository->delete($policy->getName());
        //                }
        //            }
        //        } catch (\Exception $e) {
        //            // Ignore errors during cleanup
        //        }
    }
}
