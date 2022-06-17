<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @package   Elasticsuite
 * @author    ElasticSuite Team <elasticsuite@smile.fr>
 * @copyright 2022 Smile
 * @license   Licensed to Smile-SA. All rights reserved. No warranty, explicit or implicit, provided.
 *            Unauthorized copying of this file, via any medium, is strictly prohibited.
 */

declare(strict_types=1);

namespace Elasticsuite\Search\Tests\Api\GraphQl;

use Elasticsuite\Fixture\Service\ElasticsearchFixturesInterface;
use Elasticsuite\Search\Elasticsearch\Request\SortOrderInterface;
use Elasticsuite\Standard\src\Test\AbstractTest;
use Elasticsuite\Standard\src\Test\ExpectedResponse;
use Elasticsuite\Standard\src\Test\RequestGraphQlToTest;
use Elasticsuite\User\Constant\Role;
use Symfony\Contracts\HttpClient\ResponseInterface;

class SearchDocumentsTest extends AbstractTest
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::loadFixture([
            __DIR__ . '/../../fixtures/catalogs.yaml',
            __DIR__ . '/../../fixtures/metadata.yaml',
            __DIR__ . '/../../fixtures/source_field.yaml',
        ]);
        self::loadElasticsearchIndexFixtures([__DIR__ . '/../../fixtures/indices.json']);
        self::loadElasticsearchDocumentFixtures([__DIR__ . '/../../fixtures/documents.json']);
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        self::deleteElasticsearchFixtures();
    }

    /**
     * @dataProvider basicSearchDataProvider
     *
     * @param string $entityType           Entity Type
     * @param string $catalogId            Catalog ID or code
     * @param ?int   $pageSize             Pagination size
     * @param ?int   $currentPage          Current page
     * @param ?array $expectedError        Expected error
     * @param ?int   $expectedItemsCount   Expected items count in (paged) response
     * @param ?int   $expectedTotalCount   Expected total items count
     * @param ?int   $expectedItemsPerPage Expected pagination items per page
     * @param ?int   $expectedLastPage     Expected number of the last page
     */
    public function testBasicSearchDocuments(
        string $entityType,
        string $catalogId,
        ?int $pageSize,
        ?int $currentPage,
        ?array $expectedError,
        ?int $expectedItemsCount,
        ?int $expectedTotalCount,
        ?int $expectedItemsPerPage,
        ?int $expectedLastPage,
        ?string $expectedIndex,
        ?float $expectedScore
    ): void {
        $user = $this->getUser(Role::ROLE_CONTRIBUTOR);

        $arguments = sprintf(
            'entityType: "%s", catalogId: "%s"',
            $entityType,
            $catalogId
        );
        if (null !== $pageSize) {
            $arguments .= sprintf(', pageSize: %d', $pageSize);
        }
        if (null !== $currentPage) {
            $arguments .= sprintf(', currentPage: %d', $currentPage);
        }

        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                        searchDocuments({$arguments}) {
                            collection {
                              id
                              score
                              index
                            }
                            paginationInfo {
                              itemsPerPage
                              lastPage
                              totalCount
                            }
                        }
                    }
                GQL,
                $user
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use (
                        $expectedError,
                        $expectedItemsCount,
                        $expectedTotalCount,
                        $expectedItemsPerPage,
                        $expectedLastPage,
                        $expectedIndex,
                        $expectedScore
                    ) {
                    if (!empty($expectedError)) {
                        $this->assertJsonContains($expectedError);
                        $this->assertJsonContains([
                            'data' => [
                                'searchDocuments' => null,
                            ],
                        ]);
                    } else {
                        $this->assertJsonContains([
                            'data' => [
                                'searchDocuments' => [
                                    'paginationInfo' => [
                                        'itemsPerPage' => $expectedItemsPerPage,
                                        'lastPage' => $expectedLastPage,
                                        'totalCount' => $expectedTotalCount,
                                    ],
                                ],
                            ],
                        ]);

                        $responseData = $response->toArray();
                        $this->assertIsArray($responseData['data']['searchDocuments']['collection']);
                        $this->assertCount($expectedItemsCount, $responseData['data']['searchDocuments']['collection']);
                        foreach ($responseData['data']['searchDocuments']['collection'] as $document) {
                            $this->assertArrayHasKey('score', $document);
                            $this->assertEquals($expectedScore, $document['score']);

                            $this->assertArrayHasKey('index', $document);
                            $this->assertEquals($expectedIndex, $document['index']);
                        }
                    }
                }
            )
        );
    }

    public function basicSearchDataProvider(): array
    {
        return [
            [
                'people',   // entity type.
                'b2c_fr',   // catalog ID.
                null,   // page size.
                null,   // current page.
                ['errors' => [['message' => 'Internal server error', 'debugMessage' => 'Entity type [people] does not exist']]], // expected error.
                null,   // expected items count.
                null,   // expected total count.
                null,   // expected items per page.
                null,   // expected last page.
                null,   // expected index.
                null,   // expected score.
            ],
            [
                'category', // entity type.
                'b2c_uk',   // catalog ID.
                null,   // page size.
                null,   // current page.
                ['errors' => [['message' => 'Internal server error', 'debugMessage' => 'Missing catalog [b2c_uk]']]], // expected error.
                null,   // expected items count.
                null,   // expected total count.
                null,   // expected items per page.
                null,   // expected last page.
                null,   // expected index.
                null,   // expected score.
            ],
            [
                'category', // entity type.
                'b2c_fr',   // catalog ID.
                null,   // page size.
                null,   // current page.
                [],     // expected error.
                0,      // expected items count.
                0,      // expected total count.
                30,     // expected items per page.
                1,      // expected last page.
                null,   // expected index.
                1.0,    // expected score.
            ],
            [
                'product',  // entity type.
                '2',    // catalog ID.
                10,     // page size.
                1,      // current page.
                [],     // expected error.
                10,     // expected items count.
                14,     // expected total count.
                10,     // expected items per page.
                2,      // expected last page.
                ElasticsearchFixturesInterface::PREFIX_TEST_INDEX . 'elasticsuite_test_b2c_en_product_20220429_153000', // expected index.
                1.0,    // expected score.
            ],
            [
                'product',  // entity type.
                'b2c_en',   // catalog ID.
                10,     // page size.
                1,      // current page.
                [],     // expected error.
                10,     // expected items count.
                14,     // expected total count.
                10,     // expected items per page.
                2,      // expected last page.
                ElasticsearchFixturesInterface::PREFIX_TEST_INDEX . 'elasticsuite_test_b2c_en_product_20220429_153000', // expected index.
                1.0,    // expected score.
            ],
            [
                'product',  // entity type.
                'b2c_en',   // catalog ID.
                10,     // page size.
                2,      // current page.
                [],     // expected error.
                4,      // expected items count.
                14,     // expected total count.
                10,     // expected items per page.
                2,      // expected last page.
                ElasticsearchFixturesInterface::PREFIX_TEST_INDEX . 'elasticsuite_test_b2c_en_product_20220429_153000', // expected index.
                1.0,    // expected score.
            ],
            [
                'product',  // entity type.
                'b2b_fr',   // catalog ID.
                null,   // page size.
                null,   // current page.
                [],     // expected error.
                12,     // expected items count.
                12,     // expected total count.
                30,     // expected items per page.
                1,      // expected last page.
                ElasticsearchFixturesInterface::PREFIX_TEST_INDEX . 'elasticsuite_test_b2b_fr_product_20220601_171005', // expected index.
                1.0,    // expected score.
            ],
            [
                'product',  // entity type.
                'b2b_fr',   // catalog ID.
                5,      // page size.
                2,      // current page.
                [],     // expected error.
                5,      // expected items count.
                12,     // expected total count.
                5,      // expected items per page.
                3,      // expected last page.
                ElasticsearchFixturesInterface::PREFIX_TEST_INDEX . 'elasticsuite_test_b2b_fr_product_20220601_171005', // expected index.
                1.0,    // expected score.
            ],
            [
                'product',  // entity type.
                'b2b_fr',   // catalog ID.
                1000,   // page size.
                null,   // current page.
                [],     // expected error.
                12,     // expected items count.
                12,     // expected total count.
                100,    // expected items per page.
                1,      // expected last page.
                ElasticsearchFixturesInterface::PREFIX_TEST_INDEX . 'elasticsuite_test_b2b_fr_product_20220601_171005', // expected index.
                1.0,    // expected score.
            ],
        ];
    }

    /**
     * @dataProvider sortedSearchDocumentsProvider
     *
     * @param string $entityType            Entity Type
     * @param string $catalogId             Catalog ID or code
     * @param int    $pageSize              Pagination size
     * @param int    $currentPage           Current page
     * @param array  $sortOrders            Sort order specifications
     * @param string $documentIdentifier    Document identifier to check ordered results
     * @param array  $expectedOrderedDocIds Expected ordered document identifiers
     */
    public function testSortedSearchDocuments(
        string $entityType,
        string $catalogId,
        int $pageSize,
        int $currentPage,
        array $sortOrders,
        string $documentIdentifier,
        array $expectedOrderedDocIds
    ): void {
        $user = $this->getUser(Role::ROLE_CONTRIBUTOR);

        $arguments = sprintf(
            'entityType: "%s", catalogId: "%s", pageSize: %d, currentPage: %d',
            $entityType,
            $catalogId,
            $pageSize,
            $currentPage
        );

        if (!empty($sortOrders)) {
            $sortArguments = [];
            foreach ($sortOrders as $field => $direction) {
                $sortArguments[] = sprintf('field: "%s", direction: %s', $field, $direction);
            }
            $arguments .= sprintf(', sort: {%s}', implode(', ', $sortArguments));
        }

        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                        searchDocuments({$arguments}) {
                            collection {
                              id
                              score
                              source
                            }
                            paginationInfo {
                              itemsPerPage
                            }
                        }
                    }
                GQL,
                $user
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use (
                    $pageSize,
                    $documentIdentifier,
                    $expectedOrderedDocIds
                ) {
                    $this->assertJsonContains([
                        'data' => [
                            'searchDocuments' => [
                                'paginationInfo' => [
                                    'itemsPerPage' => $pageSize,
                                ],
                            ],
                        ],
                    ]);

                    $responseData = $response->toArray();
                    $this->assertIsArray($responseData['data']['searchDocuments']['collection']);
                    $this->assertCount(\count($expectedOrderedDocIds), $responseData['data']['searchDocuments']['collection']);
                    foreach ($responseData['data']['searchDocuments']['collection'] as $index => $document) {
                        /*
                        $this->assertArrayHasKey('score', $document);
                        $this->assertEquals($expectedScore, $document['score']);
                        */
                        $this->assertArrayHasKey('id', $document);
                        $this->assertEquals("/documents/{$expectedOrderedDocIds[$index]}", $document['id']);

                        $this->assertArrayHasKey('source', $document);
                        if (\array_key_exists($documentIdentifier, $document['source'])) {
                            $this->assertEquals($expectedOrderedDocIds[$index], $document['source'][$documentIdentifier]);
                        }
                    }
                }
            )
        );
    }

    public function sortedSearchDocumentsProvider(): array
    {
        return [
            [
                'product',  // entity type.
                'b2c_en',   // catalog ID.
                10,     // page size.
                1,      // current page.
                [],     // sort order specifications.
                'entity_id', // document data identifier.
                // score DESC first, then id DESC but field 'id' is not present, so missing _first
                // which means the document will be sorted as they were imported.
                // the document.id matched here is the document._id which is entity_id (see fixtures import)
                [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],    // expected ordered document IDs
            ],
            [
                'product',  // entity type.
                'b2b_fr',   // catalog ID.
                10,     // page size.
                1,      // current page.
                [],     // sort order specifications.
                'id', // document data identifier.
                // score DESC first, then id DESC which exists in 'b2b_fr'
                // but id DESC w/missing _first, so doc w/entity_id="1" is first
                [1, 12, 11, 10, 9, 8, 7, 6, 5, 4],    // expected ordered document IDs
            ],
            [
                'product',  // entity type.
                'b2b_fr',   // catalog ID.
                10,     // page size.
                1,      // current page.
                ['id' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'id', // document data identifier.
                // id ASC (missing _last), then score DESC (but not for first doc w/ entity_id="1")
                [2, 3, 4, 5, 6, 7, 8, 9, 10, 11],    // expected ordered document IDs
            ],
            [
                'product',  // entity type.
                'b2b_fr',   // catalog ID.
                10,     // page size.
                1,      // current page.
                ['size' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'id', // document data identifier.
                // size ASC, then score DESC first, then id DESC (missing _first)
                [10, 5, 8, 11, 2, 4, 3, 6, 9, 7],   // expected ordered document IDs
            ],
            [
                'product',  // entity type.
                'b2b_fr',   // catalog ID.
                10,     // page size.
                1,      // current page.
                ['size' => SortOrderInterface::SORT_DESC], // sort order specifications.
                'id', // document data identifier.
                // size DESC, then score ASC first, then id ASC (missing _last)
                [12, 1, 7, 9, 6, 3, 4, 2, 11, 8],   // expected ordered document IDs
            ],
            [
                'product',  // entity type.
                'b2b_fr',   // catalog ID.
                5,     // page size.
                1,      // current page.
                ['price.final_price' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'id', // document data identifier.
                // price.final_price ASC, then score DESC first, then id DESC (missing _first)
                [2, 1, 3, 12, 11],   // expected ordered document IDs
            ],
        ];
    }
}
