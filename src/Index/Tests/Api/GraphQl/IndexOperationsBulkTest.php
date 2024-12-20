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

use Gally\Fixture\Service\ElasticsearchFixturesInterface;
use Gally\Test\AbstractTestCase;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestGraphQlToTest;
use Gally\User\Constant\Role;
use Gally\User\Entity\User;
use Symfony\Contracts\HttpClient\ResponseInterface;

class IndexOperationsBulkTest extends AbstractTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::loadElasticsearchIndexFixtures([__DIR__ . '/../../fixtures/indices.json']);
        self::loadElasticsearchDocumentFixtures([__DIR__ . '/../../fixtures/documents.json']);
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        self::deleteElasticsearchFixtures();
    }

    /**
     * @dataProvider bulkIndexDataProvider
     */
    public function testBulkIndex(string $indexName, array $data, ?User $user, array $expectedData): void
    {
        $data = addslashes(json_encode($data));
        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    mutation {
                      bulkIndex(input: {
                        indexName: "{$indexName}",
                        data: "{$data}"
                      }) {
                        index { name }
                      }
                    }
                GQL,
                $user
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use ($expectedData) {
                    if (\array_key_exists('error', $expectedData)) {
                        $this->assertGraphQlError($expectedData['error']);
                    } else {
                        $this->assertJsonContains($expectedData);
                    }
                }
            )
        );
    }

    public function bulkIndexDataProvider(): iterable
    {
        $admin = $this->getUser(Role::ROLE_ADMIN);
        $indexName = ElasticsearchFixturesInterface::PREFIX_TEST_INDEX . 'index_product';
        $documents = [
            'id1' => ['id' => 'id1', 'name' => 'Test doc 1', 'size' => 12],
            'id2' => ['id' => 'id2', 'name' => 'Test doc 2', 'size' => 8],
            'id3' => ['id' => 'id3', 'name' => 'Test doc 3', 'size' => 5],
        ];

        yield [$indexName, $documents, null, ['error' => 'Access Denied.']];
        yield [$indexName, $documents, $this->getUser(Role::ROLE_CONTRIBUTOR), ['error' => 'Access Denied.']];
        yield [$indexName, $documents, $admin, ['data' => ['bulkIndex' => ['index' => ['name' => $indexName]]]]];
        yield ['wrongName', $documents, $admin, ['error' => 'Index with name [wrongName] does not exist']];

        $documents['id2'] = ['id' => 'id2', 'name' => 'Test doc 2', 'size' => 'wrongSize'];
        $message = 'Bulk index operation failed 1 times in index gally_test__index_product. ' .
            'Error (mapper_parsing_exception) : failed to parse field [size] of type [integer] ' .
            'in document with id \'id2\'. Preview of field\'s value: \'wrongSize\'. Failed doc ids sample : id2.';
        yield [$indexName, $documents, $admin, ['error' => $message]];
    }

    /**
     * @dataProvider bulkDeleteDataProvider
     */
    public function testBulkDeleteIndex(string $indexName, array $ids, ?User $user, array $expectedData): void
    {
        $ids = json_encode($ids);
        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    mutation {
                      bulkDeleteIndex(input: {
                        indexName: "{$indexName}",
                        ids: $ids
                      }) {
                        index { name }
                      }
                    }
                GQL,
                $user
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use ($expectedData) {
                    if (\array_key_exists('error', $expectedData)) {
                        $this->assertGraphQlError($expectedData['error']);
                    } else {
                        $this->assertJsonContains($expectedData);
                    }
                }
            )
        );
    }

    public function bulkDeleteDataProvider(): iterable
    {
        $admin = $this->getUser(Role::ROLE_ADMIN);
        $indexName = ElasticsearchFixturesInterface::PREFIX_TEST_INDEX . 'index_product';
        $ids = ['test_1', 'test_2', 'test_3'];

        yield [$indexName, $ids, null, ['error' => 'Access Denied.']];
        yield [$indexName, $ids, $this->getUser(Role::ROLE_CONTRIBUTOR), ['error' => 'Access Denied.']];
        yield [$indexName, $ids, $admin, ['data' => ['bulkDeleteIndex' => ['index' => ['name' => $indexName]]]]];
        yield ['wrongName', $ids, $admin, ['error' => 'Index with name [wrongName] does not exist']];

        $ids[] = 'test_wrongId';
        yield [$indexName, $ids, $admin, ['data' => ['bulkDeleteIndex' => ['index' => ['name' => $indexName]]]]];
    }
}
