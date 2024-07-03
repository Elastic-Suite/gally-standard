<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @package   Gally
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Gally\Search\Tests\Api\GraphQl;

use Gally\Fixture\Service\ElasticsearchFixturesInterface;
use Gally\Metadata\Service\PriceGroupProvider;
use Gally\Metadata\Service\ReferenceLocationProvider;
use Gally\Search\Elasticsearch\Request\SortOrderInterface;
use Gally\Test\AbstractTest;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestGraphQlToTest;
use Gally\User\Constant\Role;
use Symfony\Contracts\HttpClient\ResponseInterface;

class SearchDocumentsTest extends AbstractTest
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::loadFixture([
            __DIR__ . '/../../fixtures/facet_configuration_search_documents.yaml',
            __DIR__ . '/../../fixtures/facet_configuration.yaml',
            __DIR__ . '/../../fixtures/source_field_option_label.yaml',
            __DIR__ . '/../../fixtures/source_field_option.yaml',
            __DIR__ . '/../../fixtures/source_field_label.yaml',
            __DIR__ . '/../../fixtures/source_field_search_documents.yaml',
            __DIR__ . '/../../fixtures/source_field.yaml',
            __DIR__ . '/../../fixtures/category_configurations.yaml',
            __DIR__ . '/../../fixtures/categories.yaml',
            __DIR__ . '/../../fixtures/catalogs.yaml',
            __DIR__ . '/../../fixtures/metadata.yaml',
        ]);
        self::createEntityElasticsearchIndices('product_document');
        self::createEntityElasticsearchIndices('category');
        self::loadElasticsearchDocumentFixtures([__DIR__ . '/../../fixtures/documents.json']);
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        self::deleteEntityElasticsearchIndices('product_document');
        self::deleteEntityElasticsearchIndices('category');
    }

    /**
     * @dataProvider basicSearchDataProvider
     *
     * @param string  $entityType           Entity Type
     * @param string  $catalogId            Catalog ID or code
     * @param ?int    $pageSize             Pagination size
     * @param ?int    $currentPage          Current page
     * @param ?array  $expectedError        Expected error
     * @param ?int    $expectedItemsCount   Expected items count in (paged) response
     * @param ?int    $expectedTotalCount   Expected total items count
     * @param ?int    $expectedItemsPerPage Expected pagination items per page
     * @param ?int    $expectedLastPage     Expected number of the last page
     * @param ?string $expectedIndexAlias   Expected index alias
     * @param ?float  $expectedScore        Expected documents score
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
        ?string $expectedIndexAlias,
        ?float $expectedScore
    ): void {
        $user = $this->getUser(Role::ROLE_CONTRIBUTOR);

        $arguments = sprintf(
            'entityType: "%s", localizedCatalog: "%s"',
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
                        documents({$arguments}) {
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
                        $expectedIndexAlias,
                        $expectedScore
                    ) {
                    if (!empty($expectedError)) {
                        $this->assertJsonContains($expectedError);
                        $this->assertJsonContains([
                            'data' => [
                                'documents' => null,
                            ],
                        ]);
                    } else {
                        $this->assertJsonContains([
                            'data' => [
                                'documents' => [
                                    'paginationInfo' => [
                                        'itemsPerPage' => $expectedItemsPerPage,
                                        'lastPage' => $expectedLastPage,
                                        'totalCount' => $expectedTotalCount,
                                    ],
                                    'collection' => [],
                                ],
                            ],
                        ]);

                        $responseData = $response->toArray();
                        $this->assertIsArray($responseData['data']['documents']['collection']);
                        $this->assertCount($expectedItemsCount, $responseData['data']['documents']['collection']);
                        foreach ($responseData['data']['documents']['collection'] as $document) {
                            $this->assertArrayHasKey('score', $document);
                            $this->assertEquals($expectedScore, $document['score']);

                            $this->assertArrayHasKey('index', $document);
                            $this->assertStringStartsWith($expectedIndexAlias, $document['index']);
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
                null,   // expected index alias.
                null,   // expected score.
            ],
            [
                'category', // entity type.
                'b2c_uk',   // catalog ID.
                null,   // page size.
                null,   // current page.
                ['errors' => [['message' => 'Internal server error', 'debugMessage' => 'Missing localized catalog [b2c_uk]']]], // expected error.
                null,   // expected items count.
                null,   // expected total count.
                null,   // expected items per page.
                null,   // expected last page.
                null,   // expected index alias.
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
                null,   // expected index alias.
                1.0,    // expected score.
            ],
            [
                'product_document',  // entity type.
                '2',    // catalog ID.
                10,     // page size.
                1,      // current page.
                [],     // expected error.
                10,     // expected items count.
                14,     // expected total count.
                10,     // expected items per page.
                2,      // expected last page.
                ElasticsearchFixturesInterface::PREFIX_TEST_INDEX . 'gally_b2c_en_product_document', // expected index .
                1.0,    // expected score.
            ],
            [
                'product_document',  // entity type.
                'b2c_en',   // catalog ID.
                10,     // page size.
                1,      // current page.
                [],     // expected error.
                10,     // expected items count.
                14,     // expected total count.
                10,     // expected items per page.
                2,      // expected last page.
                ElasticsearchFixturesInterface::PREFIX_TEST_INDEX . 'gally_b2c_en_product_document', // expected index .
                1.0,    // expected score.
            ],
            [
                'product_document',  // entity type.
                'b2c_en',   // catalog ID.
                10,     // page size.
                2,      // current page.
                [],     // expected error.
                4,      // expected items count.
                14,     // expected total count.
                10,     // expected items per page.
                2,      // expected last page.
                ElasticsearchFixturesInterface::PREFIX_TEST_INDEX . 'gally_b2c_en_product_document', // expected index .
                1.0,    // expected score.
            ],
            [
                'product_document',  // entity type.
                'b2b_fr',   // catalog ID.
                null,   // page size.
                null,   // current page.
                [],     // expected error.
                12,     // expected items count.
                12,     // expected total count.
                30,     // expected items per page.
                1,      // expected last page.
                ElasticsearchFixturesInterface::PREFIX_TEST_INDEX . 'gally_b2b_fr_product_document', // expected index .
                1.0,    // expected score.
            ],
            [
                'product_document',  // entity type.
                'b2b_fr',   // catalog ID.
                5,      // page size.
                2,      // current page.
                [],     // expected error.
                5,      // expected items count.
                12,     // expected total count.
                5,      // expected items per page.
                3,      // expected last page.
                ElasticsearchFixturesInterface::PREFIX_TEST_INDEX . 'gally_b2b_fr_product_document', // expected index .
                1.0,    // expected score.
            ],
            [
                'product_document',  // entity type.
                'b2b_fr',   // catalog ID.
                1000,   // page size.
                null,   // current page.
                [],     // expected error.
                12,     // expected items count.
                12,     // expected total count.
                100,    // expected items per page.
                1,      // expected last page.
                ElasticsearchFixturesInterface::PREFIX_TEST_INDEX . 'gally_b2b_fr_product_document', // expected index .
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
     * @param string $priceGroupId          Price group id
     */
    public function testSortedSearchDocuments(
        string $entityType,
        string $catalogId,
        int $pageSize,
        int $currentPage,
        array $sortOrders,
        string $documentIdentifier,
        array $expectedOrderedDocIds,
        string $priceGroupId = '0',
        string $referenceLocation = '48.913066, 2.298293'
    ): void {
        $user = $this->getUser(Role::ROLE_CONTRIBUTOR);

        $arguments = sprintf(
            'entityType: "%s", localizedCatalog: "%s", pageSize: %d, currentPage: %d',
            $entityType,
            $catalogId,
            $pageSize,
            $currentPage
        );

        $this->addSortOrders($sortOrders, $arguments);

        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                        documents({$arguments}) {
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
                $user,
                [
                    PriceGroupProvider::PRICE_GROUP_ID => $priceGroupId,
                    ReferenceLocationProvider::REFERENCE_LOCATION => $referenceLocation,
                ]
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
                            'documents' => [
                                'paginationInfo' => [
                                    'itemsPerPage' => $pageSize,
                                ],
                                'collection' => [],
                            ],
                        ],
                    ]);

                    $responseData = $response->toArray();
                    $this->assertIsArray($responseData['data']['documents']['collection']);
                    $this->assertCount(\count($expectedOrderedDocIds), $responseData['data']['documents']['collection']);
                    foreach ($responseData['data']['documents']['collection'] as $index => $document) {
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
                'product_document',  // entity type.
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
                'product_document',  // entity type.
                'b2c_en',   // catalog ID.
                10,     // page size.
                1,      // current page.
                ['_score' => SortOrderInterface::SORT_DESC], // sort order specifications.
                'entity_id', // document data identifier.
                // Explicite _score sort definition
                [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],    // expected ordered document IDs
            ],
            [
                'product_document',  // entity type.
                'b2b_fr',   // catalog ID.
                10,     // page size.
                1,      // current page.
                [],     // sort order specifications.
                'id', // document data identifier.
                // score DESC first, then id DESC which exists in 'b2b_fr'
                // but id DESC w/missing _first, so doc w/entity_id="1" is first
                // as id are string, "2" is considered greater than "12"
                [1, 9, 8, 7, 6, 5, 4, 3, 2, 12],    // expected ordered document IDs
            ],
            [
                'product_document',  // entity type.
                'b2b_fr',   // catalog ID.
                10,     // page size.
                1,      // current page.
                ['fake_source_field' => SortOrderInterface::SORT_DESC], // sort order specifications.
                'id', // document data identifier.
                // default sort order applied as the source field doesn't exist
                [10, 11, 12, 2, 3, 4, 5, 6, 7, 8],    // expected ordered document IDs
            ],
            [
                'product_document',  // entity type.
                'b2b_fr',   // catalog ID.
                10,     // page size.
                1,      // current page.
                ['seller_reference' => SortOrderInterface::SORT_DESC], // sort order specifications.
                'id', // document data identifier.
                // default sort order applied as the source field is of type "text" and it is not sortable
                [10, 11, 12, 2, 3, 4, 5, 6, 7, 8],    // expected ordered document IDs
            ],
            [
                'product_document',  // entity type.
                'b2b_fr',   // catalog ID.
                10,     // page size.
                1,      // current page.
                ['_score' => SortOrderInterface::SORT_DESC], // sort order specifications.
                'id', // document data identifier.
                // score DESC first, then id DESC which exists in 'b2b_fr'
                // but id DESC w/missing _first, so doc w/entity_id="1" is first
                // as id are string, "2" is considered greater than "12"
                [1, 9, 8, 7, 6, 5, 4, 3, 2, 12],    // expected ordered document IDs
            ],
            [
                'product_document',  // entity type.
                'b2b_fr',   // catalog ID.
                10,     // page size.
                1,      // current page.
                ['id' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'id', // document data identifier.
                // id ASC (missing _last), then score DESC (but not for first doc w/ entity_id="1")
                [10, 11, 12, 2, 3, 4, 5, 6, 7, 8],    // expected ordered document IDs
            ],
            [
                'product_document',  // entity type.
                'b2b_fr',   // catalog ID.
                10,     // page size.
                1,      // current page.
                ['size' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'id', // document data identifier.
                // size ASC, then score DESC first, then id DESC (missing _first)
                [5, 8, 2, 11, 4, 3, 6, 9, 7, 1],   // expected ordered document IDs
            ],
            [
                'product_document',  // entity type.
                'b2b_fr',   // catalog ID.
                10,     // page size.
                1,      // current page.
                ['size' => SortOrderInterface::SORT_DESC], // sort order specifications.
                'id', // document data identifier.
                // size DESC, then score ASC first, then id ASC (missing _last)
                [10, 12, 1, 7, 9, 6, 3, 4, 11, 2],   // expected ordered document IDs
            ],
            [
                'product_document',  // entity type.
                'b2b_fr',   // catalog ID.
                5,     // page size.
                1,      // current page.
                ['price.price' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'id', // document data identifier.
                // price.price ASC, then score DESC first, then id DESC (missing _first)
                [2, 1, 3, 9, 8],   // expected ordered document IDs
            ],
            [
                'product_document',  // entity type.
                'b2b_fr',   // catalog ID.
                5,     // page size.
                1,      // current page.
                ['my_price.price' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'id', // document data identifier.
                // price.price ASC, then score DESC first, then id DESC (missing _first)
                [2, 1, 3, 9, 8],   // expected ordered document IDs
                '0',
            ],
            [
                'product_document',  // entity type.
                'b2b_fr',   // catalog ID.
                5,     // page size.
                1,      // current page.
                ['my_price.price' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'id', // document data identifier.
                // price.price ASC, then score DESC first, then id DESC (missing _first)
                [1, 2, 3, 9, 8],   // expected ordered document IDs
                '1',
            ],
            [
                'product_document',  // entity type.
                'b2b_fr',   // catalog ID.
                5,     // page size.
                1,      // current page.
                ['my_price.price' => SortOrderInterface::SORT_DESC], // sort order specifications.
                'id', // document data identifier.
                // price.price ASC, then score DESC first, then id DESC (missing _first)
                [10, 11, 12, 2, 3],   // expected ordered document IDs
                'fake_price_group_id',
            ],
            [
                'product_document',  // entity type.
                'b2b_fr',   // catalog ID.
                5,     // page size.
                1,      // current page.
                ['created_at' => SortOrderInterface::SORT_DESC], // sort order specifications.
                'id', // document data identifier.
                // created_at ASC, then score DESC first, then id DESC (missing _first)
                [10, 9, 5, 2, 3],   // expected ordered document IDs
            ],
            [
                'product_document',  // entity type.
                'b2b_fr',   // catalog ID.
                5,     // page size.
                1,      // current page.
                ['manufacture_location' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'id', // document data identifier.
                // manufacture_location ASC, then score DESC first, then id DESC (missing _first)
                [1, 8, 7, 6, 12],   // expected ordered document IDs
            ],
            [
                'product_document',  // entity type.
                'b2b_fr',   // catalog ID.
                5,     // page size.
                1,      // current page.
                ['manufacture_location' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'id', // document data identifier.
                // manufacture_location ASC, then score DESC first, then id DESC (missing _first)
                [5, 4, 3, 2, 1],   // expected ordered document IDs
                '0', // Price group id
                '45.770000, 4.890000', // Reference location
            ],
        ];
    }

    /**
     * @dataProvider sortInfoSearchDocumentsProvider
     *
     * @param string $entityType                 Entity Type
     * @param string $catalogId                  Catalog ID or code
     * @param int    $pageSize                   Pagination size
     * @param int    $currentPage                Current page
     * @param array  $sortOrders                 Sort order specifications
     * @param string $expectedSortOrderField     Expected sort order field
     * @param string $expectedSortOrderDirection Expected sort order direction
     */
    public function testSortInfoSearchDocuments(
        string $entityType,
        string $catalogId,
        int $pageSize,
        int $currentPage,
        array $sortOrders,
        string $expectedSortOrderField,
        string $expectedSortOrderDirection
    ): void {
        $user = $this->getUser(Role::ROLE_CONTRIBUTOR);

        $arguments = sprintf(
            'entityType: "%s", localizedCatalog: "%s", pageSize: %d, currentPage: %d',
            $entityType,
            $catalogId,
            $pageSize,
            $currentPage
        );

        $this->addSortOrders($sortOrders, $arguments);

        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                        documents({$arguments}) {
                            collection {
                              id
                              score
                              source
                            }
                            paginationInfo {
                              itemsPerPage
                            }
                            sortInfo {
                              current {
                                field
                                direction
                              }
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
                    $expectedSortOrderField,
                    $expectedSortOrderDirection
                ) {
                    $this->assertJsonContains([
                        'data' => [
                            'documents' => [
                                'paginationInfo' => [
                                    'itemsPerPage' => $pageSize,
                                ],
                                'sortInfo' => [
                                    'current' => [
                                        [
                                            'field' => $expectedSortOrderField,
                                            'direction' => $expectedSortOrderDirection,
                                        ],
                                    ],
                                ],
                                'collection' => [],
                            ],
                        ],
                    ]);
                }
            )
        );
    }

    public function sortInfoSearchDocumentsProvider(): array
    {
        return [
            [
                'product_document',  // entity type.
                'b2c_en',   // catalog ID.
                10,     // page size.
                1,      // current page.
                [],     // sort order specifications.
                '_score', // expected sort order field.
                SortOrderInterface::SORT_DESC, // expected sort order direction.
            ],
            [
                'product_document',  // entity type.
                'b2b_fr',   // catalog ID.
                10,     // page size.
                1,      // current page.
                [],     // sort order specifications.
                '_score', // expected sort order field.
                SortOrderInterface::SORT_DESC, // expected sort order direction.
            ],
            [
                'product_document',  // entity type.
                'b2b_fr',   // catalog ID.
                10,     // page size.
                1,      // current page.
                ['id' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'id', // expected sort order field.
                SortOrderInterface::SORT_ASC, // expected sort order direction.
            ],
            [
                'product_document',  // entity type.
                'b2b_fr',   // catalog ID.
                10,     // page size.
                1,      // current page.
                ['size' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'size', // expected sort order field.
                SortOrderInterface::SORT_ASC, // expected sort order direction.
            ],
            [
                'product_document',  // entity type.
                'b2b_fr',   // catalog ID.
                10,     // page size.
                1,      // current page.
                ['size' => SortOrderInterface::SORT_DESC], // sort order specifications.
                'size', // expected sort order field.
                SortOrderInterface::SORT_DESC, // expected sort order direction.
            ],
            [
                'product_document',  // entity type.
                'b2b_fr',   // catalog ID.
                5,     // page size.
                1,      // current page.
                ['price.price' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'price__price', // expected sort order field.
                SortOrderInterface::SORT_ASC, // expected sort order direction.
            ],
            [
                'product_document',  // entity type.
                'b2b_fr',   // catalog ID.
                5,     // page size.
                1,      // current page.
                ['price__price' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'price__price', // expected sort order field.
                SortOrderInterface::SORT_ASC, // expected sort order direction.
            ],
            [
                'product_document',  // entity type.
                'b2b_fr',   // catalog ID.
                5,     // page size.
                1,      // current page.
                ['manufacture_location' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'manufacture_location', // expected sort order field.
                SortOrderInterface::SORT_ASC, // expected sort order direction.
            ],
        ];
    }

    /**
     * @dataProvider searchWithAggregationDataProvider
     *
     * @param string      $entityType           Entity Type
     * @param string      $catalogId            Catalog ID or code
     * @param int         $pageSize             Pagination size
     * @param int         $currentPage          Current page
     * @param array       $expectedAggregations expected aggregations sample
     * @param string      $priceGroupId         Price group id
     * @param string|null $query                Query text
     * @param string|null $requestType          Request type
     */
    public function testSearchDocumentsWithAggregation(
        string $entityType,
        string $catalogId,
        int $pageSize,
        int $currentPage,
        array $expectedAggregations,
        string $priceGroupId = '0',
        ?string $query = null,
        ?string $requestType = null,
    ): void {
        $user = $this->getUser(Role::ROLE_CONTRIBUTOR);

        $arguments = sprintf(
            'entityType: "%s", localizedCatalog: "%s", pageSize: %d, currentPage: %d',
            $entityType,
            $catalogId,
            $pageSize,
            $currentPage
        );

        if (null !== $query) {
            $arguments .= sprintf(', search: "%s"', $query);
        }

        if (null !== $requestType) {
            $arguments .= sprintf(', requestType: %s', $requestType);
        }

        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                        documents({$arguments}) {
                            collection {
                              id
                              score
                              source
                            }
                            aggregations {
                              field
                              count
                              label
                              type
                              date_format
                              date_range_interval
                              hasMore
                              options {
                                label
                                value
                                count
                              }
                            }
                        }
                    }
                GQL,
                $user,
                [PriceGroupProvider::PRICE_GROUP_ID => $priceGroupId]
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use ($expectedAggregations) {
                    // Extra test on response structure because all exceptions might not throw an HTTP error code.
                    $this->assertJsonContains([
                        'data' => [
                            'documents' => [
                                'aggregations' => $expectedAggregations,
                            ],
                        ],
                    ]);
                    $responseData = $response->toArray();
                    $this->assertIsArray($responseData['data']['documents']['aggregations']);
                    foreach ($responseData['data']['documents']['aggregations'] as $data) {
                        $this->assertArrayHasKey('field', $data);
                        $this->assertArrayHasKey('count', $data);
                        $this->assertArrayHasKey('label', $data);
                        $this->assertArrayHasKey('options', $data);
                    }
                }
            )
        );
    }

    public function searchWithAggregationDataProvider(): array
    {
        return [
            [
                'product_document',  // entity type.
                'b2c_en',   // catalog ID.
                10,     // page size.
                1,      // current page.
                [       // expected aggregations sample.
                    ['field' => 'is_eco_friendly', 'label' => 'Is_eco_friendly', 'type' => 'boolean', 'hasMore' => false],
                    ['field' => 'weight', 'label' => 'Weight', 'type' => 'slider', 'hasMore' => false],
                    [
                        'field' => 'category__id',
                        'label' => 'Category',
                        'type' => 'category',
                        'hasMore' => false,
                        'options' => [
                            [
                                'label' => 'One',
                                'value' => 'cat_1',
                                'count' => 2,
                            ],
                            [
                                'label' => 'Five',
                                'value' => 'cat_5',
                                'count' => 1,
                            ],
                        ],
                    ],
                    ['field' => 'size', 'label' => 'Size', 'type' => 'slider', 'hasMore' => false],
                    [
                        'field' => 'created_at',
                        'label' => 'Created_at',
                        'type' => 'date_histogram',
                        'date_format' => 'yyyy-MM-dd',
                        'date_range_interval' => '1d',
                        'hasMore' => false,
                        'options' => [
                            [
                                'label' => '2022-09-01',
                                'value' => '2022-09-01',
                                'count' => 8,
                            ],
                            [
                                'label' => '2022-09-05',
                                'value' => '2022-09-05',
                                'count' => 3,
                            ],
                        ],
                    ],
                    [
                        'field' => 'color_full__value',
                        'label' => 'Color',
                        'type' => 'checkbox',
                        'hasMore' => false,
                        'options' => [
                            [
                                'label' => 'Red',
                                'value' => 'red',
                                'count' => 1,
                            ],
                            [
                                'label' => 'Grey',
                                'value' => 'grey',
                                'count' => 6,
                            ],
                        ],
                    ],
                    [
                        'field' => 'manufacture_location',
                        'label' => 'Manufacture_location',
                        'type' => 'histogram',
                        'hasMore' => false,
                        'options' => [
                            [
                                'label' => '200.0km and more',
                                'value' => '200.0-*',
                                'count' => 12,
                            ],
                        ],
                    ],
                    [
                        'field' => 'color__value',
                        'label' => 'Color',
                        'type' => 'checkbox',
                        'hasMore' => true,
                        'options' => [
                            [
                                'label' => 'Red',
                                'value' => 'red',
                                'count' => 1,
                            ],
                            [
                                'label' => 'Grey',
                                'value' => 'grey',
                                'count' => 6,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'product_document',  // entity type.
                'b2b_fr',   // catalog ID.
                10,     // page size.
                1,      // current page.
                [       // expected aggregations sample.
                    ['field' => 'is_eco_friendly', 'label' => 'Is_eco_friendly', 'type' => 'boolean', 'hasMore' => false],
                    ['field' => 'weight', 'label' => 'Weight', 'type' => 'slider'],
                    [
                        'field' => 'category__id',
                        'label' => 'Category',
                        'type' => 'category',
                        'hasMore' => false,
                        'options' => [
                            [
                                'label' => 'Un',
                                'value' => 'cat_1',
                                'count' => 2,
                            ],
                            [
                                'label' => 'Deux',
                                'value' => 'cat_2',
                                'count' => 1,
                            ],
                        ],
                    ],
                    [
                        'field' => 'size',
                        'label' => 'Taille',
                        'type' => 'slider',
                        'hasMore' => false,
                    ],
                    [
                        'field' => 'created_at',
                        'label' => 'Created_at',
                        'type' => 'date_histogram',
                        'date_format' => 'yyyy-MM-dd',
                        'date_range_interval' => '1d',
                        'hasMore' => false,
                        'options' => [
                            [
                                'label' => '2022-09-01',
                                'value' => '2022-09-01',
                                'count' => 6,
                            ],
                            [
                                'label' => '2022-09-05',
                                'value' => '2022-09-05',
                                'count' => 3,
                            ],
                        ],
                    ],
                    [
                        'field' => 'color_full__value',
                        'label' => 'Couleur',
                        'type' => 'checkbox',
                        'hasMore' => false,
                        'options' => [
                            [
                                'label' => 'Rouge',
                                'value' => 'red',
                                'count' => 1,
                            ],
                            [
                                'label' => 'Gris',
                                'value' => 'grey',
                                'count' => 5,
                            ],
                        ],
                    ],
                    [
                        'field' => 'manufacture_location',
                        'label' => 'Manufacture_location',
                        'type' => 'histogram',
                        'hasMore' => false,
                        'options' => [
                            [
                                'label' => 'Plus de 200.0km',
                                'value' => '200.0-*',
                                'count' => 10,
                            ],
                        ],
                    ],
                    [
                        'field' => 'my_price__price',
                        'label' => 'My_price',
                        'type' => 'slider',
                        'options' => [
                            [
                                'label' => '8',
                                'value' => '8',
                                'count' => 1,
                            ],
                            [
                                'label' => '10',
                                'value' => '10',
                                'count' => 1,
                            ],
                        ],
                    ],
                    [
                        'field' => 'color__value',
                        'label' => 'Couleur',
                        'type' => 'checkbox',
                        'hasMore' => true,
                        'options' => [
                            [
                                'label' => 'Rouge',
                                'value' => 'red',
                                'count' => 1,
                            ],
                            [
                                'label' => 'Gris',
                                'value' => 'grey',
                                'count' => 5,
                            ],
                        ],
                    ],
                ],
                '0',
            ],
            [
                'product_document',  // entity type.
                'b2b_fr',   // catalog ID.
                10,     // page size.
                1,      // current page.
                [       // expected aggregations sample.
                    ['field' => 'is_eco_friendly', 'label' => 'Is_eco_friendly', 'type' => 'boolean', 'hasMore' => false],
                    ['field' => 'weight', 'label' => 'Weight', 'type' => 'slider'],
                    [
                        'field' => 'category__id',
                        'label' => 'Category',
                        'type' => 'category',
                        'hasMore' => false,
                        'options' => [
                            [
                                'label' => 'Un',
                                'value' => 'cat_1',
                                'count' => 2,
                            ],
                            [
                                'label' => 'Deux',
                                'value' => 'cat_2',
                                'count' => 1,
                            ],
                        ],
                    ],
                    [
                        'field' => 'size',
                        'label' => 'Taille',
                        'type' => 'slider',
                        'hasMore' => false,
                    ],
                    [
                        'field' => 'created_at',
                        'label' => 'Created_at',
                        'type' => 'date_histogram',
                        'date_format' => 'yyyy-MM-dd',
                        'date_range_interval' => '1d',
                        'hasMore' => false,
                        'options' => [
                            [
                                'label' => '2022-09-01',
                                'value' => '2022-09-01',
                                'count' => 6,
                            ],
                            [
                                'label' => '2022-09-05',
                                'value' => '2022-09-05',
                                'count' => 3,
                            ],
                        ],
                    ],
                    [
                        'field' => 'color_full__value',
                        'label' => 'Couleur',
                        'type' => 'checkbox',
                        'hasMore' => false,
                        'options' => [
                            [
                                'label' => 'Rouge',
                                'value' => 'red',
                                'count' => 1,
                            ],
                            [
                                'label' => 'Gris',
                                'value' => 'grey',
                                'count' => 5,
                            ],
                        ],
                    ],
                    [
                        'field' => 'manufacture_location',
                        'label' => 'Manufacture_location',
                        'type' => 'histogram',
                        'hasMore' => false,
                        'options' => [
                            [
                                'label' => 'Plus de 200.0km',
                                'value' => '200.0-*',
                                'count' => 10,
                            ],
                        ],
                    ],
                    [
                        'field' => 'my_price__price',
                        'label' => 'My_price',
                        'type' => 'slider',
                        'options' => [
                            [
                                'label' => '10',
                                'value' => '10',
                                'count' => 1,
                            ],
                            [
                                'label' => '17',
                                'value' => '17',
                                'count' => 1,
                            ],
                        ],
                    ],
                    [
                        'field' => 'color__value',
                        'label' => 'Couleur',
                        'type' => 'checkbox',
                        'hasMore' => true,
                        'options' => [
                            [
                                'label' => 'Rouge',
                                'value' => 'red',
                                'count' => 1,
                            ],
                            [
                                'label' => 'Gris',
                                'value' => 'grey',
                                'count' => 5,
                            ],
                        ],
                    ],
                ],
                '1',
            ],
            [
                'product_document',  // entity type.
                'b2b_fr',   // catalog ID.
                10,     // page size.
                1,      // current page.
                [       // expected aggregations sample.
                    ['field' => 'is_eco_friendly', 'label' => 'Is_eco_friendly', 'type' => 'boolean', 'hasMore' => false],
                    ['field' => 'weight', 'label' => 'Weight', 'type' => 'slider'],
                    [
                        'field' => 'category__id',
                        'label' => 'Category',
                        'type' => 'category',
                        'hasMore' => false,
                        'options' => [
                            [
                                'label' => 'Un',
                                'value' => 'cat_1',
                                'count' => 2,
                            ],
                            [
                                'label' => 'Deux',
                                'value' => 'cat_2',
                                'count' => 1,
                            ],
                        ],
                    ],
                    [
                        'field' => 'size',
                        'label' => 'Taille',
                        'type' => 'slider',
                        'hasMore' => false,
                    ],
                    [
                        'field' => 'created_at',
                        'label' => 'Created_at',
                        'type' => 'date_histogram',
                        'date_format' => 'yyyy-MM-dd',
                        'date_range_interval' => '1d',
                        'hasMore' => false,
                        'options' => [
                            [
                                'label' => '2022-09-01',
                                'value' => '2022-09-01',
                                'count' => 6,
                            ],
                            [
                                'label' => '2022-09-05',
                                'value' => '2022-09-05',
                                'count' => 3,
                            ],
                        ],
                    ],
                    [
                        'field' => 'color_full__value',
                        'label' => 'Couleur',
                        'type' => 'checkbox',
                        'hasMore' => false,
                        'options' => [
                            [
                                'label' => 'Rouge',
                                'value' => 'red',
                                'count' => 1,
                            ],
                            [
                                'label' => 'Gris',
                                'value' => 'grey',
                                'count' => 5,
                            ],
                        ],
                    ],
                    [
                        'field' => 'manufacture_location',
                        'label' => 'Manufacture_location',
                        'type' => 'histogram',
                        'hasMore' => false,
                        'options' => [
                            [
                                'label' => 'Plus de 200.0km',
                                'value' => '200.0-*',
                                'count' => 10,
                            ],
                        ],
                    ],
                    [
                        'field' => 'color__value',
                        'label' => 'Couleur',
                        'type' => 'checkbox',
                        'hasMore' => true,
                        'options' => [
                            [
                                'label' => 'Rouge',
                                'value' => 'red',
                                'count' => 1,
                            ],
                            [
                                'label' => 'Gris',
                                'value' => 'grey',
                                'count' => 5,
                            ],
                        ],
                    ],
                ],
                'fake_price_group_id',
            ],
            [ // Test autocomplete aggregations
                'product_document',  // entity type.
                'b2c_en',   // catalog ID.
                10,     // page size.
                1,      // current page.
                [       // expected aggregations sample.
                    [
                        'field' => 'color__value',
                        'label' => 'Color',
                        'type' => 'checkbox',
                        'hasMore' => true,
                        'options' => [
                            [
                                'label' => 'Black',
                                'value' => 'black',
                                'count' => 6,
                            ],
                            [
                                'label' => 'Grey',
                                'value' => 'grey',
                                'count' => 3,
                            ],
                            [
                                'label' => 'Blue',
                                'value' => 'blue',
                                'count' => 1,
                            ],
                        ],
                    ],
                    [
                        'field' => 'category__id',
                        'label' => 'Category',
                        'type' => 'category',
                        'hasMore' => false,
                        'options' => [
                            [
                                'label' => 'One',
                                'value' => 'cat_1',
                                'count' => 2,
                            ],
                            [
                                'label' => 'Five',
                                'value' => 'cat_5',
                                'count' => 1,
                            ],
                        ],
                    ],
                    ['field' => 'weight', 'label' => 'Weight', 'type' => 'slider', 'hasMore' => false],
                    ['field' => 'is_eco_friendly', 'label' => 'Is_eco_friendly', 'type' => 'boolean', 'hasMore' => false],
                ],
                '0',
                'bag', // query
                'autocomplete', // request type
            ],
        ];
    }

    /**
     * @dataProvider filteredSearchDocumentsValidationProvider
     *
     * @param string $entityType   Entity Type
     * @param string $catalogId    Catalog ID or code
     * @param string $filter       Filters to apply
     * @param string $debugMessage Expected debug message
     */
    public function testFilteredSearchDocumentsGraphQlValidation(
        string $entityType,
        string $catalogId,
        string $filter,
        string $debugMessage
    ): void {
        $user = $this->getUser(Role::ROLE_CONTRIBUTOR);

        $arguments = sprintf(
            'entityType: "%s", localizedCatalog: "%s", filter: [%s]',
            $entityType,
            $catalogId,
            $filter
        );

        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                        documents({$arguments}) {
                            collection {
                              id
                            }
                        }
                    }
                GQL,
                $user
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use (
                    $debugMessage
                ) {
                    $this->assertJsonContains([
                        'errors' => [
                            [
                                'debugMessage' => $debugMessage,
                            ],
                        ],
                    ]);
                }
            )
        );
    }

    public function filteredSearchDocumentsValidationProvider(): array
    {
        return [
            [
                'product_document', // entity type.
                'b2c_en', // catalog ID.
                '{matchFilter: {field:"fake_source_field_match", match:"sacs"}}', // Filters.
                "The field 'fake_source_field_match' does not exist", // debug message
            ],
            [
                'product_document', // entity type.
                'b2c_en', // catalog ID.
                '{equalFilter: {field:"fake_source_field_equal", eq: "24-MB03"}}', // Filters.
                "The field 'fake_source_field_equal' does not exist", // debug message
            ],
            [
                'product_document', // entity type.
                'b2c_en', // catalog ID.
                '{rangeFilter: {field:"fake_source_field_range", gt: "0"}}', // Filters.
                "The field 'fake_source_field_range' does not exist", // debug message
            ],
            [
                'product_document', // entity type.
                'b2c_en', // catalog ID.
                '{matchFilter: {field:"fake_source_field", match:"sacs"}}', // Filters.
                "The field 'fake_source_field' does not exist", // debug message
            ],
            [
                'product_document', // entity type.
                'b2c_en', // catalog ID.
                '{rangeFilter: {field:"id"}}', // Filters.
                "Filter argument rangeFilter: At least 'gt', 'lt', 'gte' or 'lte' should be filled.", // debug message
            ],
            [
                'product_document', // entity type.
                'b2c_en', // catalog ID.
                '{rangeFilter: {field:"id", gt: "1", gte: "1"}}', // Filters.
                "Filter argument rangeFilter: Do not use 'gt' and 'gte' in the same filter.", // debug message
            ],
            [
                'product_document', // entity type.
                'b2c_en', // catalog ID.
                '{equalFilter:{field:"id"}}', // Filters.
                "Filter argument equalFilter: At least 'eq' or 'in' should be filled.", // debug message
            ],
            [
                'product_document', // entity type.
                'b2c_en', // catalog ID.
                '{equalFilter:{field:"id" eq: "1" in:["1"]}}', // Filters.
                "Filter argument equalFilter: Only 'eq' or only 'in' should be filled, not both.", // debug message
            ],
            [
                'product_document', // entity type.
                'b2c_en', // catalog ID.
                '{distanceFilter:{field:"id" lte: 100}}', // Filters.
                'Filter argument distanceFilter: The field id should be of type geo_point.', // debug message
            ],
        ];
    }

    /**
     * @dataProvider filteredSearchDocumentsProvider
     *
     * @param string $entityType            Entity Type
     * @param string $catalogId             Catalog ID or code
     * @param int    $pageSize              Pagination size
     * @param int    $currentPage           Current page
     * @param string $filter                Filters to apply
     * @param array  $sortOrders            Sort order specifications
     * @param string $documentIdentifier    Document identifier to check ordered results
     * @param array  $expectedOrderedDocIds Expected ordered document identifiers
     */
    public function testFilteredSearchDocuments(
        string $entityType,
        string $catalogId,
        int $pageSize,
        int $currentPage,
        array $sortOrders,
        string $filter,
        string $documentIdentifier,
        array $expectedOrderedDocIds,
        ?string $referenceLocation = null,
    ): void {
        $user = $this->getUser(Role::ROLE_CONTRIBUTOR);

        $arguments = sprintf(
            'entityType: "%s", localizedCatalog: "%s", pageSize: %d, currentPage: %d, filter: [%s]',
            $entityType,
            $catalogId,
            $pageSize,
            $currentPage,
            $filter
        );
        $headers = [];
        if ($referenceLocation) {
            $headers['reference-location'] = $referenceLocation;
        }

        $this->addSortOrders($sortOrders, $arguments);

        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                        documents({$arguments}) {
                            collection {
                              id
                              source
                            }
                        }
                    }
                GQL,
                $user,
                $headers
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use (
                    $documentIdentifier,
                    $expectedOrderedDocIds
                ) {
                    // Extra test on response structure because all exceptions might not throw an HTTP error code.
                    $this->assertJsonContains([
                        'data' => [
                            'documents' => [
                                'collection' => [],
                            ],
                        ],
                    ]);

                    $responseData = $response->toArray();
                    $this->assertIsArray($responseData['data']['documents']['collection']);
                    $this->assertCount(\count($expectedOrderedDocIds), $responseData['data']['documents']['collection']);
                    foreach ($responseData['data']['documents']['collection'] as $index => $document) {
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

    public function filteredSearchDocumentsProvider(): array
    {
        return [
            [
                'product_document', // entity type.
                'b2b_fr', // catalog ID.
                10, // page size.
                1,  // current page.
                [], // sort order specifications.
                '{equalFilter: {field: "sku", eq: "24-MB03"}}',
                'entity_id', // document data identifier.
                [3], // expected ordered document IDs
            ],
            [
                'product_document', // entity type.
                'b2b_fr', // catalog ID.
                10, // page size.
                1,  // current page.
                ['id' => SortOrderInterface::SORT_ASC], // sort order specifications.
                '{equalFilter: {field: "sku", in: ["24-MB02", "24-WB01"]}}', // filter.
                'entity_id', // document data identifier.
                [6, 8], // expected ordered document IDs
            ],
            [
                'product_document', // entity type.
                'b2b_fr', // catalog ID.
                10, // page size.
                1,  // current page.
                ['id' => SortOrderInterface::SORT_ASC], // sort order specifications.
                '{rangeFilter: {field:"id", gte: "10", lte: "12"}}', // filter.
                'entity_id', // document data identifier.
                [10, 11, 12], // expected ordered document IDs
            ],
            [
                'product_document', // entity type.
                'b2b_fr', // catalog ID.
                10, // page size.
                1,  // current page.
                ['id' => SortOrderInterface::SORT_ASC], // sort order specifications.
                '{rangeFilter: {field:"id", gt: "10", lt: "12"}}', // filter.
                'entity_id', // document data identifier.
                [11], // expected ordered document IDs
            ],
            [
                'product_document', // entity type.
                'b2b_fr', // catalog ID.
                10, // page size.
                1,  // current page.
                ['id' => SortOrderInterface::SORT_ASC], // sort order specifications.
                '{matchFilter: {field: "name", match: "Compete Track"}}', // filter.
                'entity_id', // document data identifier.
                [9], // expected ordered document IDs
            ],
            [
                'product_document', // entity type.
                'b2b_fr', // catalog ID.
                10, // page size.
                1,  // current page.
                ['id' => SortOrderInterface::SORT_ASC], // sort order specifications.
                '{existFilter: {field: "size"}}', // filter.
                'entity_id', // document data identifier.
                [11, 12, 2, 3, 4, 5, 6, 7, 8, 9], // expected ordered document IDs
            ],
            [
                'product_document', // entity type.
                'b2b_fr', // catalog ID.
                10, // page size.
                1,  // current page.
                ['id' => SortOrderInterface::SORT_ASC], // sort order specifications.
                <<<GQL
                  {matchFilter: {field:"name", match:"Sac"}}
                  {equalFilter: {field: "sku", in: ["24-WB06", "24-WB03"]}}
                GQL, // filter.
                'entity_id', // document data identifier.
                [11, 12], // expected ordered document IDs
            ],
            [
                'product_document', // entity type.
                'b2b_fr', // catalog ID.
                10, // page size.
                1,  // current page.
                ['id' => SortOrderInterface::SORT_ASC], // sort order specifications.
                <<<GQL
                  {boolFilter: {
                    _must: [
                      {matchFilter: {field:"name", match:"Sac"}}
                      {equalFilter: {field: "sku", in: ["24-WB06", "24-WB03"]}}
                    ]}
                  }
                GQL, // filter.
                'entity_id', // document data identifier.
                [11, 12], // expected ordered document IDs
            ],
            [
                'product_document', // entity type.
                'b2b_fr', // catalog ID.
                10, // page size.
                1,  // current page.
                ['id' => SortOrderInterface::SORT_ASC], // sort order specifications.
                <<<GQL
                  {equalFilter: {field: "sku", in: ["24-WB06", "24-WB03", "24-WB05"]}}
                  {equalFilter: {field:"color__value", eq:"black"}},
                GQL, // filter.
                'entity_id', // document data identifier.
                [11, 12], // expected ordered document IDs
            ],
            [
                'product_document', // entity type.
                'b2b_fr', // catalog ID.
                10, // page size.
                1,  // current page.
                ['id' => SortOrderInterface::SORT_ASC], // sort order specifications.
                <<<GQL
                  {boolFilter: {
                    _not: [
                      {existFilter: {field:"size"}}
                    ]}
                  }
                GQL, // filter.
                'entity_id', // document data identifier.
                [10], // expected ordered document IDs
            ],
            [
                'product_document', // entity type.
                'b2b_fr', // catalog ID.
                10, // page size.
                1,  // current page.
                ['id' => SortOrderInterface::SORT_ASC], // sort order specifications.
                <<<GQL
                  {boolFilter: {
                    _should: [
                      {equalFilter: {field: "sku", eq: "24-MB05"}}
                      {equalFilter: {field: "sku", eq: "24-UB02"}}
                    ]}
                  }
                GQL, // filter.
                'entity_id', // document data identifier.
                [4, 7], // expected ordered document IDs
            ],
            [
                'product_document', // entity type.
                'b2b_fr', // catalog ID.
                10, // page size.
                1,  // current page.
                ['id' => SortOrderInterface::SORT_ASC], // sort order specifications.
                <<<GQL
                  {boolFilter: {
                    _not: [
                      {matchFilter: {field:"name", match:"Sac"}}
                    ]}
                  }
                GQL, // filter.
                'entity_id', // document data identifier.
                [10, 5, 9], // expected ordered document IDs
            ],
            [
                'product_document', // entity type.
                'b2b_fr', // catalog ID.
                10, // page size.
                1,  // current page.
                ['id' => SortOrderInterface::SORT_ASC], // sort order specifications.
                <<<GQL
                  {boolFilter: {
                    _must: [
                      {matchFilter: {field:"name", match:"Sac"}}
                    ]
                    _should: [
                      {equalFilter: {field: "sku", eq: "24-WB06"}}
                      {equalFilter: {field: "sku", eq: "24-WB03"}}
                    ]}
                  }
                GQL, // filter.
                'entity_id', // document data identifier.
                [11, 12], // expected ordered document IDs
            ],
            [
                'product_document', // entity type.
                'b2b_fr', // catalog ID.
                10, // page size.
                1,  // current page.
                ['id' => SortOrderInterface::SORT_ASC], // sort order specifications.
                <<<GQL
                  {boolFilter: {
                    _must: [
                      {matchFilter: {field:"name", match:"Sac"}}
                    ]
                    _should: [
                      {equalFilter: {field: "sku", eq: "24-WB01"}}
                      {equalFilter: {field: "sku", eq: "24-WB06"}}
                      {equalFilter: {field: "sku", eq: "24-WB03"}}
                    ]
                    _not: [
                      {equalFilter: {field: "id", eq: "11"}}
                    ]}
                  }
                GQL, // filter.
                'entity_id', // document data identifier.
                [12, 8], // expected ordered document IDs
            ],
            [
                'product_document', // entity type.
                'b2b_fr', // catalog ID.
                10, // page size.
                1,  // current page.
                ['id' => SortOrderInterface::SORT_ASC], // sort order specifications.
                <<<GQL
                  {boolFilter: {
                    _must: [
                      {matchFilter: {field:"name", match:"Sac"}}
                      {boolFilter: {
                        _should: [
                          {equalFilter: {field: "sku", eq: "24-WB06"}}
                          {equalFilter: {field: "sku", eq: "24-WB03"}}
                        ]}
                      }
                    ]}
                  }
                GQL, // filter.
                'entity_id', // document data identifier.
                [11, 12], // expected ordered document IDs
            ],
            [
                'product_document', // entity type.
                'b2c_en', // catalog ID.
                10, // page size.
                1,  // current page.
                [], // sort order specifications.
                '{rangeFilter: {field: "created_at", lte: "2022-09-04"}}',
                'entity_id', // document data identifier.
                [1, 6, 7, 8, 9, 11, 12, 13], // expected ordered document IDs
            ],
            [
                'product_document', // entity type.
                'b2c_en', // catalog ID.
                10, // page size.
                1,  // current page.
                ['manufacture_location' => SortOrderInterface::SORT_ASC], // sort order specifications.
                '{distanceFilter: {field: "manufacture_location", lte: 350}}',
                'entity_id', // document data identifier.
                [1, 6, 7, 8, 9, 11, 12, 13], // expected ordered document IDs
            ],
            [
                'product_document', // entity type.
                'b2c_en', // catalog ID.
                10, // page size.
                1,  // current page.
                ['manufacture_location' => SortOrderInterface::SORT_ASC], // sort order specifications.
                '{distanceFilter: {field: "manufacture_location", gte: 350, lte: 500}}',
                'entity_id', // document data identifier.
                [5], // expected ordered document IDs
            ],
            [
                'product_document', // entity type.
                'b2c_en', // catalog ID.
                10, // page size.
                1,  // current page.
                ['manufacture_location' => SortOrderInterface::SORT_ASC], // sort order specifications.
                '{distanceFilter: {field: "manufacture_location", gte: 350}}',
                'entity_id', // document data identifier.
                [5, 2, 3, 4, 10, 14], // expected ordered document IDs
            ],
            [
                'product_document', // entity type.
                'b2c_en', // catalog ID.
                10, // page size.
                1,  // current page.
                ['manufacture_location' => SortOrderInterface::SORT_ASC], // sort order specifications.
                '{distanceFilter: {field: "manufacture_location", lte: 400}}',
                'entity_id', // document data identifier.
                [1, 6, 7, 8, 9, 11, 12, 13, 2, 3], // expected ordered document IDs
                '44.832196, -0.554729', // reference location
            ],
        ];
    }

    /**
     * @dataProvider searchWithQueryDataProvider
     *
     * @param string   $entityType          Entity Type
     * @param string   $catalogId           Catalog ID or code
     * @param string   $query               Query text
     * @param int      $expectedResultCount Expected result count
     * @param string[] $expectedResultNames Expected result names
     */
    public function testSearchDocumentsWithQuery(
        string $entityType,
        string $catalogId,
        string $query,
        int $expectedResultCount,
        array $expectedResultNames,
    ): void {
        $user = $this->getUser(Role::ROLE_CONTRIBUTOR);

        $arguments = sprintf(
            'entityType: "%s", localizedCatalog: "%s", pageSize: %d, currentPage: %d, search: "%s"',
            $entityType,
            $catalogId,
            10,
            1,
            $query
        );

        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                        documents({$arguments}) {
                            collection {
                              id
                              score
                              source
                            }
                        }
                    }
                GQL,
                $user
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use ($expectedResultCount, $expectedResultNames) {
                    $data = $response->toArray();
                    $this->assertArrayNotHasKey(
                        'errors',
                        $data,
                        isset($data['errors']) ? json_encode($data['errors']) : ''
                    );

                    // Extra test on response structure because all exceptions might not throw an HTTP error code.
                    $this->assertJsonContains([
                        'data' => [
                            'documents' => [
                                'collection' => [],
                            ],
                        ],
                    ]);
                    $responseData = $response->toArray();
                    $results = $responseData['data']['documents']['collection'];
                    $names = array_map(fn (array $item) => $item['source']['name'], $results);
                    $this->assertCount($expectedResultCount, $results);
                    $this->assertEquals($expectedResultNames, $names);
                }
            )
        );
    }

    public function searchWithQueryDataProvider(): array
    {
        return [
            // Search reference field
            [
                'product_document', // entity type.
                'b2c_en',           // catalog ID.
                'striveshoulder',   // query.
                1,                  // expected result count.
                [                   // expected result name.
                    'Strive Shoulder Pack',
                ],
            ],

            // Search a word
            [
                'product_document', // entity type.
                'b2c_en',           // catalog ID.
                'bag',              // query.
                7,                  // expected result count.
                [                   // expected result name.
                    'Wayfarer Messenger Bag',
                    'Joust Duffle Bag',
                    'Voyage Yoga Bag',
                    'Push It Messenger Bag',
                    'Rival Field Messenger',
                    'Strive Shoulder Pack',
                    'Crown Summit Backpack',
                ],
            ],

            // Search a non-existing word
            [
                'product_document', // entity type.
                'b2c_fr',           // catalog ID.
                'bag',              // query.
                0,                  // expected result count.
                [],                // expected result name.
            ],

            // Search in description field
            [
                'product_document', // entity type.
                'b2c_en',           // catalog ID.
                'summer',           // query.
                2,                  // expected result count.
                [                   // expected result name.
                    'Rival Field Messenger',
                    'Crown Summit Backpack',
                ],
            ],

            // Search in multiple field
            [
                'product_document', // entity type.
                'b2c_en',           // catalog ID.
                'yoga',             // query.
                2,                  // expected result count.
                [                   // expected result name.
                    'Voyage Yoga Bag',
                    'Crown Summit Backpack',
                ],
            ],

            // Search with multiple words
            [
                'product_document', // entity type.
                'b2c_en',           // catalog ID.
                'bag autumn',       // query.
                1,                  // expected result count.
                [                   // expected result name.
                    'Wayfarer Messenger Bag',
                ],
            ],

            // Search with misspelled word
            [
                'product_document', // entity type.
                'b2c_en',           // catalog ID.
                'bag automn',       // query.
                1,                  // expected result count.
                [                   // expected result name.
                    'Wayfarer Messenger Bag',
                ],
            ],

            // Search with word with same phonetic
            [
                'product_document', // entity type.
                'b2c_en',           // catalog ID.
                'bohqpaq',          // query.
                4,                  // expected result count.
                [                   // expected result name.
                    'Fusion Backpack',
                    'Driven Backpack',
                    'Endeavor Daytrip Backpack',
                    'Crown Summit Backpack',
                ],
            ],

            // Search with words from name and select attribute spellchecked
            [
                'product_document', // entity type.
                'b2c_en',           // catalog ID.
                'Testtt Duffle',     // query.
                1,                  // expected result count.
                [                   // expected result name.
                    'Joust Duffle Bag',
                ],
            ],

            // Search with words from name and select attribute not spellchecked
            [
                'product_document', // entity type.
                'b2c_en',           // catalog ID.
                'red backpack',     // query.
                1,                  // expected result count.
                [                   // expected result name.
                    'Fusion Backpack',
                ],
            ],

            // Search with sku
            [
                'product_document', // entity type.
                'b2c_en',           // catalog ID.
                '24-MB04',          // query.
                1,                  // expected result count.
                [                   // expected result name.
                    'Strive Shoulder Pack',
                ],
            ],
            [
                'product_document', // entity type.
                'b2c_en',           // catalog ID.
                '24MB04',           // query.
                1,                  // expected result count.
                [                   // expected result name.
                    'Strive Shoulder Pack',
                ],
            ],
            [
                'product_document', // entity type.
                'b2c_en',           // catalog ID.
                '24 MB 04',         // query.
                1,                  // expected result count.
                [                   // expected result name.
                    'Strive Shoulder Pack',
                ],
            ],

            // Search with number
            [
                'product_document', // entity type.
                'b2c_en',           // catalog ID.
                '123456',           // query.
                0,                  // expected result count.
                [],
            ],

            // Search with special chars
            [
                'product_document', // entity type.
                'b2c_en',           // catalog ID.
                '(yoga)\"{}()/\\\\@:\".',  // query.
                2,                  // expected result count.
                [
                    'Voyage Yoga Bag',
                    'Crown Summit Backpack',
                ],
            ],

            // Search with various utf8 chars.
            [
                'product_document', // entity type.
                'b2c_en',           // catalog ID.
                "£¨µùµ㈀㌫\xc3\xb1", // query.
                0,                  // expected result count.
                [],
            ],
        ];
    }

    /**
     * @dataProvider searchWithAggregationAndFilterDataProvider
     *
     * @param string      $entityType           Entity Type
     * @param string      $catalogId            Catalog ID or code
     * @param int         $pageSize             Pagination size
     * @param int         $currentPage          Current page
     * @param string|null $filter               Filters to apply
     * @param array       $expectedOptionsCount expected aggregation option count
     */
    public function testSearchDocumentsWithAggregationAndFilter(
        string $entityType,
        string $catalogId,
        int $pageSize,
        int $currentPage,
        ?string $filter,
        array $expectedOptionsCount,
    ): void {
        $user = $this->getUser(Role::ROLE_CONTRIBUTOR);

        $arguments = sprintf(
            'entityType: "%s", localizedCatalog: "%s", pageSize: %d, currentPage: %d',
            $entityType,
            $catalogId,
            $pageSize,
            $currentPage,
        );
        if ($filter) {
            $arguments = sprintf(
                'entityType: "%s", localizedCatalog: "%s", pageSize: %d, currentPage: %d, filter: [%s]',
                $entityType,
                $catalogId,
                $pageSize,
                $currentPage,
                $filter,
            );
        }

        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                        documents({$arguments}) {
                            aggregations {
                              field
                              count
                              options {
                                value
                              }
                            }
                        }
                    }
                GQL,
                $user
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use ($expectedOptionsCount) {
                    $responseData = $response->toArray();
                    $this->assertIsArray($responseData['data']['documents']['aggregations']);
                    foreach ($responseData['data']['documents']['aggregations'] as $data) {
                        if (\array_key_exists($data['field'], $expectedOptionsCount)) {
                            $this->assertCount($expectedOptionsCount[$data['field']], $data['options'] ?? []);
                        }
                    }
                }
            )
        );
    }

    public function searchWithAggregationAndFilterDataProvider(): array
    {
        return [
            [
                'product_document',  // entity type.
                'b2c_en',   // catalog ID.
                10,     // page size.
                1,      // current page.
                null, // filter.
                [ // expected option result
                    'color__value' => 2,
                    'color_full__value' => 9,
                    'category__id' => 2,
                    'is_eco_friendly' => 2,
                ],
            ],
            [
                'product_document',  // entity type.
                'b2c_en',   // catalog ID.
                10,     // page size.
                1,      // current page.
                '{equalFilter: {field: "sku", eq: "24-WB05"}}', // filter.
                [ // expected option result
                    'color__value' => 1,
                    'color_full__value' => 1,
                    'category__id' => 0,
                    'is_eco_friendly' => 1,
                ],
            ],
            [
                'product_document',  // entity type.
                'b2c_en',   // catalog ID.
                10,     // page size.
                1,      // current page.
                '{equalFilter: {field: "color.value", in: ["pink"]}}', // filter.
                [ // expected option result
                    'color__value' => 2,
                    'color_full__value' => 4,
                    'category__id' => 0,
                    'is_eco_friendly' => 1,
                ],
            ],
        ];
    }

    /**
     * @dataProvider searchWithAggregationAndFilterDataProvider
     *
     * @param string      $entityType           Entity Type
     * @param string      $catalogId            Catalog ID or code
     * @param int         $pageSize             Pagination size
     * @param int         $currentPage          Current page
     * @param string|null $filter               Filters to apply
     * @param array       $expectedOptionsCount expected aggregation option count
     */
    public function testSearchDocumentsWithDateField(
        string $entityType,
        string $catalogId,
        int $pageSize,
        int $currentPage,
        ?string $filter,
        array $expectedOptionsCount,
    ): void {
        $user = $this->getUser(Role::ROLE_CONTRIBUTOR);

        $arguments = sprintf(
            'entityType: "%s", localizedCatalog: "%s", pageSize: %d, currentPage: %d',
            $entityType,
            $catalogId,
            $pageSize,
            $currentPage,
        );
        if ($filter) {
            $arguments = sprintf(
                'entityType: "%s", localizedCatalog: "%s", pageSize: %d, currentPage: %d, filter: [%s]',
                $entityType,
                $catalogId,
                $pageSize,
                $currentPage,
                $filter,
            );
        }

        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                        documents({$arguments}) {
                            aggregations {
                              field
                              count
                              options {
                                value
                              }
                            }
                        }
                    }
                GQL,
                $user
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use ($expectedOptionsCount) {
                    $responseData = $response->toArray();
                    $this->assertIsArray($responseData['data']['documents']['aggregations']);
                    foreach ($responseData['data']['documents']['aggregations'] as $data) {
                        if (\array_key_exists($data['field'], $expectedOptionsCount)) {
                            $this->assertCount($expectedOptionsCount[$data['field']], $data['options'] ?? []);
                        }
                    }
                }
            )
        );
    }

    private function addSortOrders(array $sortOrders, string &$arguments): void
    {
        if (!empty($sortOrders)) {
            $sortArguments = [];
            foreach ($sortOrders as $field => $direction) {
                $sortArguments[] = sprintf('field: "%s", direction: %s', $field, $direction);
            }
            $arguments .= sprintf(', sort: {%s}', implode(', ', $sortArguments));
        }
    }
}
