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

namespace Gally\Product\Tests\Api\GraphQl;

use Gally\Fixture\Service\ElasticsearchFixturesInterface;
use Gally\Metadata\Service\PriceGroupProvider;
use Gally\Metadata\Service\ReferenceLocationProvider;
use Gally\Search\Elasticsearch\Request\SortOrderInterface;
use Gally\Test\AbstractTestCase;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestGraphQlToTest;
use Gally\User\Constant\Role;
use Gally\User\Entity\User;
use Symfony\Contracts\HttpClient\ResponseInterface;

class SearchProductsTest extends AbstractTestCase
{
    protected string $graphQlQuery = 'products';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::loadFixture([
            __DIR__ . '/../../fixtures/facet_configuration.yaml',
            __DIR__ . '/../../fixtures/source_field_option_label.yaml',
            __DIR__ . '/../../fixtures/source_field_option.yaml',
            __DIR__ . '/../../fixtures/source_field_label.yaml',
            __DIR__ . '/../../fixtures/source_field.yaml',
            __DIR__ . '/../../fixtures/category_configurations.yaml',
            __DIR__ . '/../../fixtures/categories.yaml',
            __DIR__ . '/../../fixtures/catalogs.yaml',
            __DIR__ . '/../../fixtures/metadata.yaml',
        ]);
        self::createEntityElasticsearchIndices('product');
        self::loadElasticsearchDocumentFixtures([__DIR__ . '/../../fixtures/product_documents.json']);
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        self::deleteEntityElasticsearchIndices('product');
    }

    /**
     * @dataProvider securityDataProvider
     */
    public function testSecurity(?User $user, ?string $expectedError): void
    {
        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                        products: {$this->graphQlQuery}(requestType: product_catalog, localizedCatalog: "b2c_en") {
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
                function (ResponseInterface $response) use ($expectedError) {
                    if ($expectedError) {
                        $this->assertGraphQlError($expectedError);
                    } else {
                        $this->assertJsonContains([
                            'data' => [
                                'products' => [
                                    'collection' => [],
                                ],
                            ],
                        ]);
                    }
                }
            )
        );
    }

    public function securityDataProvider(): array
    {
        return [
            [$this->getUser(Role::ROLE_ADMIN), null],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), null],
            [null, null],
        ];
    }

    /**
     * @dataProvider basicSearchProductsDataProvider
     *
     * @param string  $catalogId            Catalog ID or code
     * @param ?int    $pageSize             Pagination size
     * @param ?int    $currentPage          Current page
     * @param ?string $expectedError        Expected error
     * @param ?int    $expectedItemsCount   Expected items count in (paged) response
     * @param ?int    $expectedTotalCount   Expected total items count
     * @param ?int    $expectedItemsPerPage Expected pagination items per page
     * @param ?int    $expectedLastPage     Expected number of the last page
     * @param ?string $expectedIndexAlias   Expected index alias
     * @param ?float  $expectedScore        Expected score
     */
    public function testBasicSearchProducts(
        string $catalogId,
        ?int $pageSize,
        ?int $currentPage,
        ?string $expectedError,
        ?int $expectedItemsCount,
        ?int $expectedTotalCount,
        ?int $expectedItemsPerPage,
        ?int $expectedLastPage,
        ?string $expectedIndexAlias,
        ?float $expectedScore
    ): void {
        $user = $this->getUser(Role::ROLE_CONTRIBUTOR);

        $arguments = \sprintf(
            'requestType: product_catalog, localizedCatalog: "%s"',
            $catalogId
        );
        if (null !== $pageSize) {
            $arguments .= \sprintf(', pageSize: %d', $pageSize);
        }
        if (null !== $currentPage) {
            $arguments .= \sprintf(', currentPage: %d', $currentPage);
        }

        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                        products: {$this->graphQlQuery}({$arguments}) {
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
                        $this->assertGraphQlError($expectedError);
                        $this->assertJsonContains([
                            'data' => [
                                'products' => null,
                            ],
                        ]);
                    } else {
                        $this->assertJsonContains([
                            'data' => [
                                'products' => [
                                    'paginationInfo' => [
                                        'itemsPerPage' => $expectedItemsPerPage,
                                        'lastPage' => $expectedLastPage,
                                        'totalCount' => $expectedTotalCount,
                                    ],
                                ],
                            ],
                        ]);

                        $responseData = $response->toArray();
                        $this->assertIsArray($responseData['data']['products']['collection']);
                        $this->assertCount($expectedItemsCount, $responseData['data']['products']['collection']);
                        foreach ($responseData['data']['products']['collection'] as $document) {
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

    public function basicSearchProductsDataProvider(): array
    {
        return [
            [
                'b2c_uk',   // catalog ID.
                null,   // page size.
                null,   // current page.
                'Missing localized catalog [b2c_uk]', // expected error.
                null,   // expected items count.
                null,   // expected total count.
                null,   // expected items per page.
                null,   // expected last page.
                null,   // expected index.
                null,   // expected score.
            ],
            [
                '2',    // catalog ID.
                10,     // page size.
                1,      // current page.
                null,   // expected error.
                10,     // expected items count.
                14,     // expected total count.
                10,     // expected items per page.
                2,      // expected last page.
                ElasticsearchFixturesInterface::PREFIX_TEST_INDEX . 'gally_b2c_en_product', // expected index alias.
                1.0,    // expected score.
            ],
            [
                'b2c_en',   // catalog ID.
                10,     // page size.
                1,      // current page.
                null,   // expected error.
                10,     // expected items count.
                14,     // expected total count.
                10,     // expected items per page.
                2,      // expected last page.
                ElasticsearchFixturesInterface::PREFIX_TEST_INDEX . 'gally_b2c_en_product', // expected index alias.
                1.0,    // expected score.
            ],
            [
                'b2c_en',   // catalog ID.
                10,     // page size.
                2,      // current page.
                null,   // expected error.
                4,      // expected items count.
                14,     // expected total count.
                10,     // expected items per page.
                2,      // expected last page.
                ElasticsearchFixturesInterface::PREFIX_TEST_INDEX . 'gally_b2c_en_product', // expected index alias.
                1.0,    // expected score.
            ],
            [
                'b2c_fr',   // catalog ID.
                null,   // page size.
                null,   // current page.
                null,   // expected error.
                12,     // expected items count.
                12,     // expected total count.
                30,     // expected items per page.
                1,      // expected last page.
                ElasticsearchFixturesInterface::PREFIX_TEST_INDEX . 'gally_b2c_fr_product', // expected index alias.
                1.0,    // expected score.
            ],
            [
                'b2c_fr',   // catalog ID.
                5,      // page size.
                2,      // current page.
                null,   // expected error.
                5,      // expected items count.
                12,     // expected total count.
                5,      // expected items per page.
                3,      // expected last page.
                ElasticsearchFixturesInterface::PREFIX_TEST_INDEX . 'gally_b2c_fr_product', // expected index alias.
                1.0,    // expected score.
            ],
            [
                'b2c_fr',   // catalog ID.
                1000,   // page size.
                null,   // current page.
                null,   // expected error.
                12,     // expected items count.
                12,     // expected total count.
                100,    // expected items per page.
                1,      // expected last page.
                ElasticsearchFixturesInterface::PREFIX_TEST_INDEX . 'gally_b2c_fr_product', // expected indexalias.
                1.0,    // expected score.
            ],
        ];
    }

    /**
     * @dataProvider sortedSearchProductsProvider
     *
     * @param string $catalogId             Catalog ID or code
     * @param int    $pageSize              Pagination size
     * @param int    $currentPage           Current page
     * @param array  $sortOrders            Sort order specifications
     * @param string $documentIdentifier    Document identifier to check ordered results
     * @param array  $expectedOrderedDocIds Expected ordered document identifiers
     * @param string $priceGroupId          Price group id
     */
    public function testSortedSearchProducts(
        string $catalogId,
        int $pageSize,
        int $currentPage,
        array $sortOrders,
        string $documentIdentifier,
        array $expectedOrderedDocIds,
        string $priceGroupId = '0',
        ?string $currentCategoryId = null,
        ?string $currentCategoryConfiguration = null,
        ?string $referenceLocation = null
    ): void {
        $user = $this->getUser(Role::ROLE_CONTRIBUTOR);

        $arguments = \sprintf(
            'requestType: product_catalog, localizedCatalog: "%s", pageSize: %d, currentPage: %d',
            $catalogId,
            $pageSize,
            $currentPage
        );

        if ($currentCategoryId) {
            $arguments .= ", currentCategoryId: \"$currentCategoryId\"";
        }

        if (null !== $currentCategoryConfiguration) {
            $arguments .= \sprintf(', currentCategoryConfiguration: "%s"', addslashes($currentCategoryConfiguration));
        }

        if (!empty($sortOrders)) {
            $sortArguments = [];
            foreach ($sortOrders as $field => $direction) {
                $sortArguments[] = \sprintf('%s: %s', $field, $direction);
            }
            $arguments .= \sprintf(', sort: {%s}', implode(', ', $sortArguments));
        }

        $headers = [PriceGroupProvider::PRICE_GROUP_ID => $priceGroupId];
        if ($referenceLocation) {
            $headers[ReferenceLocationProvider::REFERENCE_LOCATION] = $referenceLocation;
        }

        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                        products: {$this->graphQlQuery}({$arguments}) {
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
                $headers
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
                            'products' => [
                                'paginationInfo' => [
                                    'itemsPerPage' => $pageSize,
                                ],
                            ],
                        ],
                    ]);

                    $responseData = $response->toArray();
                    $this->assertIsArray($responseData['data']['products']['collection']);
                    $this->assertCount(\count($expectedOrderedDocIds), $responseData['data']['products']['collection']);
                    foreach ($responseData['data']['products']['collection'] as $index => $document) {
                        /*
                        $this->assertArrayHasKey('score', $document);
                        $this->assertEquals($expectedScore, $document['score']);
                        */
                        $this->assertArrayHasKey('id', $document);
                        $this->assertEquals($this->getUri('products', $expectedOrderedDocIds[$index]), $document['id']);

                        $this->assertArrayHasKey('source', $document);
                        if (\array_key_exists($documentIdentifier, $document['source'])) {
                            $this->assertEquals($expectedOrderedDocIds[$index], $document['source'][$documentIdentifier]);
                        }
                    }
                }
            )
        );
    }

    public function sortedSearchProductsProvider(): array
    {
        return [
            [
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
                'b2c_fr',   // catalog ID.
                10,     // page size.
                1,      // current page.
                [],     // sort order specifications.
                'id', // document data identifier.
                // score DESC first, then id DESC which exists in 'b2c_fr'
                // but id DESC w/missing _first, so doc w/entity_id="1" is first
                ['1', 'p_12', 'p_11', 'p_10', 'p_09', 'p_08', 'p_07', 'p_06', 'p_05', 'p_04'], // expected ordered document IDs
            ],
            [
                'b2c_fr',   // catalog ID.
                10,     // page size.
                1,      // current page.
                ['id' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'id', // document data identifier.
                // id ASC (missing _last), then score DESC (but not for first doc w/ entity_id="1")
                ['p_02', 'p_03', 'p_04', 'p_05', 'p_06', 'p_07', 'p_08', 'p_09', 'p_10', 'p_11'], // expected ordered document IDs
            ],
            [
                'b2c_fr',   // catalog ID.
                10,     // page size.
                1,      // current page.
                ['size' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'id', // document data identifier.
                // size ASC, then score DESC first, then id DESC (missing _first)
                ['p_10', 'p_05', 'p_11', 'p_02', 'p_04', 'p_03', 'p_06', 'p_09', 'p_07', '1'], // expected ordered document IDs
            ],
            [
                'b2c_fr',   // catalog ID.
                10,     // page size.
                1,      // current page.
                ['size' => SortOrderInterface::SORT_DESC], // sort order specifications.
                'id', // document data identifier.
                // size DESC, then score ASC first, then id ASC (missing _last)
                ['p_08', 'p_12', '1', 'p_07', 'p_09', 'p_06', 'p_03', 'p_04', 'p_02', 'p_11'], // expected ordered document IDs
            ],
            [
                'b2c_fr',   // catalog ID.
                10,     // page size.
                1,      // current page.
                ['created_at' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'id', // document data identifier.
                // size DESC, then score ASC first, then id ASC (missing _last)
                ['1', 'p_12', 'p_11', 'p_08', 'p_07', 'p_06', 'p_04', 'p_03', 'p_02', 'p_05'], // expected ordered document IDs
            ],
            [
                'b2c_fr',   // catalog ID.
                5,     // page size.
                1,      // current page.
                ['price_as_nested__price' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'id', // document data identifier.
                // price_as_nested.price ASC, then score DESC first, then id DESC (missing _first)
                ['p_02', '1', 'p_03', 'p_12', 'p_11'],   // expected ordered document IDs
            ],
            [
                'b2c_fr',   // catalog ID.
                5,     // page size.
                1,      // current page.
                ['name' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'id', // document data identifier.
                // price_as_nested.price ASC, then score DESC first, then id DESC (missing _first)
                ['p_10', 'p_09', 'p_05', 'p_02', 'p_03'],   // expected ordered document IDs
            ],
            [
                'b2c_fr',   // catalog ID.
                5,     // page size.
                1,      // current page.
                ['brand__label' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'id', // document data identifier.
                // price_as_nested.price ASC, then score DESC first, then id DESC (missing _first)
                ['1', 'p_12', 'p_11', 'p_10', 'p_09'],   // expected ordered document IDs
            ],
            [
                'b2c_fr',   // catalog ID.
                5,     // page size.
                1,      // current page.
                ['my_price__price' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'id', // document data identifier.
                // price_as_nested.price ASC, then score DESC first, then id DESC (missing _first)
                ['p_02', '1', 'p_03', 'p_12', 'p_11'],   // expected ordered document IDs
                '0', // Price group id
            ],
            [
                'b2c_fr',   // catalog ID.
                5,     // page size.
                1,      // current page.
                ['my_price__price' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'id', // document data identifier.
                // price_as_nested.price ASC, then score DESC first, then id DESC (missing _first)
                ['1', 'p_02', 'p_03', 'p_12', 'p_11'],   // expected ordered document IDs
                '1', // Price group id
            ],
            [
                'b2c_fr',   // catalog ID.
                5,     // page size.
                1,      // current page.
                ['my_price__price' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'id', // document data identifier.
                // price_as_nested.price ASC, then score DESC first, then id DESC (missing _first)
                ['1', 'p_12', 'p_11', 'p_10', 'p_09'],   // expected ordered document IDs
                'fake_price_group_id', // Price group id
            ],
            [
                'b2c_fr',   // catalog ID.
                10,     // page size.
                1,      // current page.
                [],     // sort order specifications.
                'entity_id', // document data identifier.
                // test product are sorted by price because category "cat_1" has price as default sorting option.
                ['p_02', '1'],    // expected ordered document IDs
                '0',
                'cat_1',
            ],
            [
                'b2c_en',   // catalog ID.
                10,     // page size.
                1,      // current page.
                ['manufacture_location' => SortOrderInterface::SORT_ASC],     // sort order specifications.
                'entity_id', // document data identifier.
                // test product are sorted by price because category "cat_1" has price as default sorting option.
                ['1', '6', '7', '8', '9', '11', '12', '13', '5', '2'],    // expected ordered document IDs
                '0',
            ],
            [
                'b2c_en',   // catalog ID.
                10,     // page size.
                1,      // current page.
                ['manufacture_location' => SortOrderInterface::SORT_DESC],     // sort order specifications.
                'entity_id', // document data identifier.
                // test product are sorted by price because category "cat_1" has price as default sorting option.
                ['10', '14', '2', '3', '4', '5', '1', '6', '7', '8'],    // expected ordered document IDs
                '0',
            ],
            [
                'b2c_en',   // catalog ID.
                10,     // page size.
                1,      // current page.
                ['manufacture_location' => SortOrderInterface::SORT_ASC],     // sort order specifications.
                'entity_id', // document data identifier.
                // test product are sorted by price because category "cat_1" has price as default sorting option.
                ['1', '6', '7', '8', '9', '11', '12', '13', '2', '3'],  // expected ordered document IDs
                '0',
                null,
                null,
                '44.832196, -0.554729',
            ],
        ];
    }

    /**
     * @dataProvider sortInfoSearchProductsProvider
     *
     * @param string $catalogId                  Catalog ID or code
     * @param int    $pageSize                   Pagination size
     * @param int    $currentPage                Current page
     * @param array  $sortOrders                 Sort order specifications
     * @param string $expectedSortOrderField     Expected sort order field
     * @param string $expectedSortOrderDirection Expected sort order direction
     */
    public function testSortInfoSearchProducts(
        string $catalogId,
        int $pageSize,
        int $currentPage,
        array $sortOrders,
        string $expectedSortOrderField,
        string $expectedSortOrderDirection
    ): void {
        $user = $this->getUser(Role::ROLE_CONTRIBUTOR);

        $arguments = \sprintf(
            'requestType: product_catalog, localizedCatalog: "%s", pageSize: %d, currentPage: %d',
            $catalogId,
            $pageSize,
            $currentPage
        );

        if (!empty($sortOrders)) {
            $sortArguments = [];
            foreach ($sortOrders as $field => $direction) {
                $sortArguments[] = \sprintf('%s: %s', $field, $direction);
            }
            $arguments .= \sprintf(', sort: {%s}', implode(', ', $sortArguments));
        }

        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                        products: {$this->graphQlQuery}({$arguments}) {
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
                            'products' => [
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
                            ],
                        ],
                    ]);
                }
            )
        );
    }

    public function sortInfoSearchProductsProvider(): array
    {
        return [
            [
                'b2c_en',   // catalog ID.
                10,     // page size.
                1,      // current page.
                [],     // sort order specifications.
                '_score', // expected sort order field.
                SortOrderInterface::SORT_DESC, // expected sort order direction.
            ],
            [
                'b2c_fr',   // catalog ID.
                10,     // page size.
                1,      // current page.
                [],     // sort order specifications.
                '_score', // expected sort order field.
                SortOrderInterface::SORT_DESC, // expected sort order direction.
            ],
            [
                'b2c_fr',   // catalog ID.
                10,     // page size.
                1,      // current page.
                ['id' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'id', // expected sort order field.
                SortOrderInterface::SORT_ASC, // expected sort order direction.
            ],
            [
                'b2c_fr',   // catalog ID.
                10,     // page size.
                1,      // current page.
                ['size' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'size', // expected sort order field.
                SortOrderInterface::SORT_ASC, // expected sort order direction.
            ],
            [
                'b2c_fr',   // catalog ID.
                10,     // page size.
                1,      // current page.
                ['size' => SortOrderInterface::SORT_DESC], // sort order specifications.
                'size', // expected sort order field.
                SortOrderInterface::SORT_DESC, // expected sort order direction.
            ],
            [
                'b2c_fr',   // catalog ID.
                10,     // page size.
                1,      // current page.
                ['created_at' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'created_at', // expected sort order field.
                SortOrderInterface::SORT_ASC, // expected sort order direction.
            ],
            [
                'b2c_fr',   // catalog ID.
                5,     // page size.
                1,      // current page.
                ['price_as_nested__price' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'price_as_nested__price', // expected sort order field.
                SortOrderInterface::SORT_ASC, // expected sort order direction.
            ],
            [
                'b2c_fr',   // catalog ID.
                10,     // page size.
                1,      // current page.
                ['name' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'name', // expected sort order field.
                SortOrderInterface::SORT_ASC, // expected sort order direction.
            ],
            [
                'b2c_fr',   // catalog ID.
                10,     // page size.
                1,      // current page.
                ['brand__label' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'brand__label', // expected sort order field.
                SortOrderInterface::SORT_ASC, // expected sort order direction.
            ],
        ];
    }

    public function testSortedSearchProductsInvalidField(): void
    {
        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                        products: {$this->graphQlQuery}(requestType: product_catalog, localizedCatalog: "b2c_fr", sort: { length: desc }) {
                            collection { id }
                        }
                    }
                GQL,
                null
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) {
                    $this->assertGraphQlError('Field "length" is not defined by type "ProductSortInput".');
                }
            )
        );

        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                        products: {$this->graphQlQuery}(requestType: product_catalog, localizedCatalog: "b2c_fr", sort: { stock__qty: desc }) {
                            collection { id }
                        }
                    }
                GQL,
                null
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) {
                    $this->assertGraphQlError('Field "stock__qty" is not defined by type "ProductSortInput".');
                }
            )
        );

        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                        products: {$this->graphQlQuery}(requestType: product_catalog, localizedCatalog: "b2c_fr", sort: { price__price: desc }) {
                            collection { id }
                        }
                    }
                GQL,
                null
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) {
                    $this->assertGraphQlError('Field "price__price" is not defined by type "ProductSortInput". Did you mean "my_price__price"?');
                }
            )
        );

        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                        products: {$this->graphQlQuery}(requestType: product_catalog, localizedCatalog: "b2c_fr", sort: { stock_as_nested__qty: desc }) {
                            collection { id }
                        }
                    }
                GQL,
                null
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) {
                    $this->assertGraphQlError('Field "stock_as_nested__qty" is not defined by type "ProductSortInput". Did you mean "price_as_nested__price"?');
                }
            )
        );

        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                        products: {$this->graphQlQuery}(requestType: product_catalog, localizedCatalog: "b2c_fr", sort: { id: desc, size: asc }) {
                            collection { id }
                        }
                    }
                GQL,
                null
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) {
                    $this->assertGraphQlError('Sort argument : You can\'t sort on multiple attribute.');
                }
            )
        );
    }

    /**
     * @dataProvider fulltextSearchProductsProvider
     *
     * @param string $catalogId             Catalog ID or code
     * @param int    $pageSize              Pagination size
     * @param int    $currentPage           Current page
     * @param string $searchQuery           Search query
     * @param string $documentIdentifier    Document identifier to check ordered results
     * @param array  $expectedOrderedDocIds Expected ordered document identifiers
     */
    public function testFulltextSearchProducts(
        string $catalogId,
        int $pageSize,
        int $currentPage,
        string $searchQuery,
        string $documentIdentifier,
        array $expectedOrderedDocIds
    ): void {
        $user = $this->getUser(Role::ROLE_CONTRIBUTOR);

        $arguments = \sprintf(
            'requestType: product_catalog, localizedCatalog: "%s", pageSize: %d, currentPage: %d, search: "%s"',
            $catalogId,
            $pageSize,
            $currentPage,
            $searchQuery,
        );

        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                        products: {$this->graphQlQuery}({$arguments}) {
                            collection { id score source }
                        }
                    }
                GQL,
                $user
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use ($documentIdentifier, $expectedOrderedDocIds) {
                    $data = $response->toArray();
                    $this->assertArrayNotHasKey(
                        'errors',
                        $data,
                        isset($data['errors']) ? json_encode($data['errors']) : ''
                    );
                    $this->validateExpectedResults($response, $documentIdentifier, $expectedOrderedDocIds);
                }
            )
        );
    }

    public function fulltextSearchProductsProvider(): array
    {
        return [
            [
                'b2c_en',   // catalog ID.
                10,         // page size.
                1,          // current page.
                'striveshoulder', // query.
                'id',       // document data identifier.
                [2],        // expected ordered document IDs
            ],
            [
                'b2c_en',   // catalog ID.
                10,         // page size.
                1,          // current page.
                'bag',      // query.
                'id',       // document data identifier.
                [1, 4, 8, 14, 5, 2, 3],  // expected ordered document IDs
            ],
            [
                'b2c_fr',   // catalog ID.
                10,         // page size.
                1,          // current page.
                'bag',      // query.
                'id',       // document data identifier.
                [],  // expected ordered document IDs
            ],
            [
                'b2c_en',   // catalog ID.
                10,         // page size.
                1,          // current page.
                'summer',   // query: search in description field.
                'id',       // document data identifier.
                [5, 3],  // expected ordered document IDs
            ],
            [
                'b2c_en',   // catalog ID.
                10,         // page size.
                1,          // current page.
                'yoga',      // query: search in multiple field.
                'id',       // document data identifier.
                [8, 3],  // expected ordered document IDs
            ],
            [
                'b2c_en',   // catalog ID.
                10,         // page size.
                1,          // current page.
                'bag spring', // query: search with multiple words.
                'id',       // document data identifier.
                [2],  // expected ordered document IDs
            ],
            [
                'b2c_en',   // catalog ID.
                10,         // page size.
                1,          // current page.
                'bag sprong', // query: search with misspelled word.
                'id',       // document data identifier.
                [2],  // expected ordered document IDs
            ],
            [
                'b2c_en',   // catalog ID.
                10,         // page size.
                1,          // current page.
                'bohqpaq',  // query: search with word with same phonetic.
                'id',       // document data identifier.
                [6, 12, 11, 3],  // expected ordered document IDs
            ],
            [
                'b2c_en',   // catalog ID.
                10,         // page size.
                1,          // current page.
                '123456',   // query: search with number.
                'id',       // document data identifier.
                [],         // expected ordered document IDs
            ],
            [
                'b2c_en',   // catalog ID.
                10,         // page size.
                1,          // current page.
                '(yoga)\"{}()/\\\\@:\".',  // query: search with special chars.
                'id',       // document data identifier.
                [8, 3],     // expected ordered document IDs
            ],
            [
                'b2c_en',   // catalog ID.
                10,         // page size.
                1,          // current page.
                '24-MB04',  // query: search with word with same sku.
                'id',       // document data identifier.
                [2],        // expected ordered document IDs
            ],
            [
                'b2c_en',   // catalog ID.
                10,         // page size.
                1,          // current page.
                '24MB04',   // query: search with word with same sku.
                'id',       // document data identifier.
                [2],        // expected ordered document IDs
            ],
            [
                'b2c_en',   // catalog ID.
                10,         // page size.
                1,          // current page.
                '24 MB 04', // query: search with word with same sku.
                'id',       // document data identifier.
                [2],  // expected ordered document IDs
            ],
            [
                'b2c_en',   // catalog ID.
                10,         // page size.
                1,          // current page.
                "£¨µùµ㈀㌫\xc3\xb1", // query: various utf8 char.
                'id',       // document data identifier.
                [],  // expected ordered document IDs
            ],
            [
                'b2c_en',   // catalog ID.
                10,         // page size.
                1,          // current page.
                'bag autumn', // query: Verify that documents with "bag" and "autumn" close together in the description have a higher score than those with "bag" and "autumn" farther apart in the description.
                'id',       // document data identifier.
                [4, 1],  // expected ordered document IDs
            ],
        ];
    }

    /**
     * @dataProvider filteredSearchDocumentsValidationProvider
     *
     * @param string $catalogId    Catalog ID or code
     * @param string $filter       Filters to apply
     * @param string $errorMessage Expected debug message
     */
    public function testFilteredSearchProductsGraphQlValidation(
        string $catalogId,
        string $filter,
        string $errorMessage
    ): void {
        $user = $this->getUser(Role::ROLE_CONTRIBUTOR);
        $arguments = \sprintf('requestType: product_catalog, localizedCatalog: "%s", filter: {%s}', $catalogId, $filter);
        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                        products: {$this->graphQlQuery}({$arguments}) {
                            collection { id }
                        }
                    }
                GQL,
                $user
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use (
                    $errorMessage
                ) {
                    $this->assertGraphQlError($errorMessage);
                }
            )
        );
    }

    public function filteredSearchDocumentsValidationProvider(): array
    {
        return [
            [
                'b2c_en', // catalog ID.
                'fake_source_field_match: { match:"sacs" }', // Filters.
                'Field "fake_source_field_match" is not defined by type "ProductFieldFilterInput".', // debug message
            ],
            [
                'b2c_en', // catalog ID.
                'size: { match: "id" }', // Filters.
                'Field "match" is not defined by type "EntityIntegerTypeFilterInput".', // debug message
            ],
            [
                'b2c_en', // catalog ID.
                'name: { in: ["Test"], eq: "Test" }', // Filters.
                'Filter argument name: Only \'eq\', \'in\', \'match\' or \'exist\' should be filled.', // debug message
            ],
            [
                'b2c_en', // catalog ID.
                'created_at: { gt: "2022-09-23", gte: "2022-09-23" }', // Filters.
                'Filter argument created_at: Do not use \'gt\' and \'gte\' in the same filter.', // debug message
            ],
            [
                'b2c_en', // catalog ID.
                'is_eco_friendly: {}', // Filters.
                'Filter argument is_eco_friendly: At least \'eq\' or \'exist\' should be filled.', // debug message
            ],
            [
                'b2c_en', // catalog ID.
                'is_eco_friendly: { exist: true, eq: true }', // Filters.
                'Filter argument is_eco_friendly: Only \'eq\' or \'exist\' should be filled.', // debug message
            ],
            [
                'b2c_en', // catalog ID.
                'created_at: {eq: "invalid-date-format"}', // Filters.
                "Filter argument created_at: Date format for 'invalid-date-format' is not valid in operator 'eq'.", // debug message
            ],
            [
                'b2c_en', // catalog ID.
                'created_at: {gte: "2022-13-45"}', // Filters.
                "Filter argument created_at: Date format for '2022-13-45' is not valid in operator 'gte'.", // debug message
            ],
        ];
    }

    /**
     * @dataProvider filteredSearchDocumentsProvider
     *
     * @param string $catalogId             Catalog ID or code
     * @param string $filter                Filters to apply
     * @param array  $sortOrders            Sort order specifications
     * @param string $documentIdentifier    Document identifier to check ordered results
     * @param array  $expectedOrderedDocIds Expected ordered document identifiers
     * @param string $priceGroupId          Price group id
     */
    public function testFilteredSearchProducts(
        string $catalogId,
        array $sortOrders,
        string $filter,
        string $documentIdentifier,
        array $expectedOrderedDocIds,
        string $priceGroupId = '0',
        ?string $referenceLocation = null,
    ): void {
        $user = $this->getUser(Role::ROLE_CONTRIBUTOR);
        $arguments = \sprintf(
            'requestType: product_catalog, localizedCatalog: "%s", pageSize: %d, currentPage: %d, filter: {%s}',
            $catalogId,
            10,
            1,
            $filter
        );

        $this->addSortOrders($sortOrders, $arguments);

        $headers = [PriceGroupProvider::PRICE_GROUP_ID => $priceGroupId];
        if ($referenceLocation) {
            $headers[ReferenceLocationProvider::REFERENCE_LOCATION] = $referenceLocation;
        }

        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                        products: {$this->graphQlQuery}({$arguments}) {
                            collection { id source }
                        }
                    }
                GQL,
                $user,
                $headers
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use ($documentIdentifier, $expectedOrderedDocIds) {
                    $this->validateExpectedResults($response, $documentIdentifier, $expectedOrderedDocIds);
                }
            )
        );
    }

    public function filteredSearchDocumentsProvider(): array
    {
        return [
            [
                'b2c_fr', // catalog ID.
                [], // sort order specifications.
                'sku: { eq: "24-MB03" }',
                'entity_id', // document data identifier.
                ['p_03'], // expected ordered document IDs
            ],
            [
                'b2c_fr', // catalog ID.
                [], // sort order specifications.
                'category_as_nested__id: { eq: "cat_1" }',
                'entity_id', // document data identifier.
                ['1', 'p_02'], // expected ordered document IDs
            ],
            [
                'b2c_en', // catalog ID.
                [], // sort order specifications.
                'created_at: { lt: "2022-09" }',
                'entity_id', // document data identifier.
                ['8', '9'], // expected ordered document IDs
            ],
            [
                'b2c_en', // catalog ID.
                [], // sort order specifications.
                'created_at: { lte: "2022-09" }',
                'entity_id', // document data identifier.
                ['1', '2', '3', '8', '9', '11', '12', '13'], // expected ordered document IDs
            ],
            [
                'b2c_en', // catalog ID.
                [], // sort order specifications.
                'created_at: { gte: "2023-01" }',
                'entity_id', // document data identifier.
                ['6', '7'], // expected ordered document IDs
            ],
            [
                'b2c_en', // catalog ID.
                [], // sort order specifications.
                'created_at: { gt: "2023-01" }',
                'entity_id', // document data identifier.
                [], // expected ordered document IDs
            ],
            [
                'b2c_en', // catalog ID.
                [], // sort order specifications.
                'created_at: { eq: "2022-08" }',
                'entity_id', // document data identifier.
                ['8', '9'], // expected ordered document IDs
            ],
            [
                'b2c_en', // catalog ID.
                [], // sort order specifications.
                'created_at: { in: ["2022-08", "2022-11"] }',
                'entity_id', // document data identifier.
                ['5', '8', '9'], // expected ordered document IDs
            ],
            [
                'b2c_fr', // catalog ID.
                ['id' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'sku: { in: ["24-MB02", "24-WB01"] }', // filter.
                'entity_id', // document data identifier.
                ['p_06', 'p_08'], // expected ordered document IDs
            ],
            [
                'b2c_fr', // catalog ID.
                ['id' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'size: { gte: 10, lte: 12 }', // filter.
                'entity_id', // document data identifier.
                ['p_07', 'p_09', 'p_12', '1'], // expected ordered document IDs
            ],
            [
                'b2c_fr', // catalog ID.
                ['id' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'size: { gt: 10, lt: 12 }', // filter.
                'entity_id', // document data identifier.
                ['p_07', 'p_09'], // expected ordered document IDs
            ],
            [
                'b2c_fr', // catalog ID.
                ['id' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'name: { match: "Compete Track" }', // filter.
                'entity_id', // document data identifier.
                ['p_09'], // expected ordered document IDs
            ],
            [
                'b2c_fr', // catalog ID.
                ['id' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'size: { exist: true }', // filter.
                'entity_id', // document data identifier.
                ['p_02', 'p_03', 'p_04', 'p_05', 'p_06', 'p_07', 'p_09', 'p_10', 'p_11', 'p_12'], // expected ordered document IDs
            ],
            [
                'b2c_fr', // catalog ID.
                ['id' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'size: { exist: false }', // filter.
                'entity_id', // document data identifier.
                ['p_08'], // expected ordered document IDs
            ],
            [
                'b2c_fr', // catalog ID.
                ['id' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'is_eco_friendly: { eq: true }', // filter.
                'entity_id', // document data identifier.
                ['p_03', 'p_04', 'p_05', 'p_06'], // expected ordered document IDs
            ],
            [
                'b2c_fr', // catalog ID.
                ['id' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'is_eco_friendly: { eq: false }', // filter.
                'entity_id', // document data identifier.
                ['p_02', 'p_07', 'p_08', 'p_10'], // expected ordered document IDs
            ],
            [
                'b2c_fr', // catalog ID.
                ['id' => SortOrderInterface::SORT_ASC], // sort order specifications.
                <<<GQL
                  name: { match: "Sac" }
                  sku: { in: ["24-WB06", "24-WB03"] }
                GQL, // filter.
                'entity_id', // document data identifier.
                ['p_11', 'p_12'], // expected ordered document IDs
            ],
            [
                'b2c_fr', // catalog ID.
                ['id' => SortOrderInterface::SORT_ASC], // sort order specifications.
                <<<GQL
                  boolFilter: {
                    _must: [
                      { name: { match:"Sac" }}
                      { sku: { in: ["24-WB06", "24-WB03"] }}
                    ]
                   }
                GQL, // filter.
                'entity_id', // document data identifier.
                ['p_11', 'p_12'], // expected ordered document IDs
            ],
            [
                'b2c_fr', // catalog ID.
                ['id' => SortOrderInterface::SORT_ASC], // sort order specifications.
                <<<GQL
                  boolFilter: {
                    _should: [
                      { sku: { eq: "24-MB05" }}
                      { sku: { eq: "24-UB02" }}
                    ]
                   }
                GQL, // filter.
                'entity_id', // document data identifier.
                ['p_04', 'p_07'], // expected ordered document IDs
            ],
            [
                'b2c_fr', // catalog ID.
                ['id' => SortOrderInterface::SORT_ASC], // sort order specifications.
                <<<GQL
                  boolFilter: {
                    _not: [
                      { name: { match:"Sac" }}
                    ]
                  }
                GQL, // filter.
                'entity_id', // document data identifier.
                ['p_05', 'p_09', 'p_10'], // expected ordered document IDs
            ],
            [
                'b2c_fr', // catalog ID.
                ['id' => SortOrderInterface::SORT_ASC], // sort order specifications.
                <<<GQL
                  boolFilter: {
                    _must: [
                      {name: {match:"Sac"}}
                    ]
                    _should: [
                      {sku: {eq: "24-WB06"}}
                      {sku: {eq: "24-WB03"}}
                    ]
                  }
                GQL, // filter.
                'entity_id', // document data identifier.
                ['p_11', 'p_12'], // expected ordered document IDs
            ],
            [
                'b2c_fr', // catalog ID.
                ['id' => SortOrderInterface::SORT_ASC], // sort order specifications.
                <<<GQL
                  boolFilter: {
                    _must: [
                      {name: {match:"Sac"}}
                    ]
                    _should: [
                      {sku: {eq: "24-WB01"}}
                      {sku: {eq: "24-WB06"}}
                      {sku: {eq: "24-WB03"}}
                    ]
                    _not: [
                      {id: {eq: "p_11"}}
                    ]
                  }
                GQL, // filter.
                'entity_id', // document data identifier.
                ['p_08', 'p_12'], // expected ordered document IDs
            ],
            [
                'b2c_fr', // catalog ID.
                ['id' => SortOrderInterface::SORT_ASC], // sort order specifications.
                <<<GQL
                  boolFilter: {
                    _must: [
                      {name: {match:"Sac"}}
                      {boolFilter: {
                        _should: [
                          {sku: {eq: "24-WB06"}}
                          {sku: {eq: "24-WB03"}}
                        ]}
                      }
                    ]
                  }
                GQL, // filter.
                'entity_id', // document data identifier.
                ['p_11', 'p_12'], // expected ordered document IDs
            ],
            [
                'b2c_fr', // catalog ID.
                ['id' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'my_price__price: {gt: 10}', // filter.
                'entity_id', // document data identifier.
                ['p_03', '1'], // expected ordered document IDs
                '0',
            ],
            [
                'b2c_fr', // catalog ID.
                ['id' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'my_price__price: {gt: 10}', // filter.
                'entity_id', // document data identifier.
                ['p_02', 'p_03', '1'], // expected ordered document IDs
                '1',
            ],
            [
                'b2c_fr', // catalog ID.
                ['id' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'my_price__price: {gt: 10}', // filter.
                'entity_id', // document data identifier.
                [], // expected ordered document IDs
                'fake_price_group_id',
            ],
            [
                'b2c_en', // catalog ID.
                ['manufacture_location' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'manufacture_location: {lte: 10}', // filter.
                'entity_id', // document data identifier.
                [], // expected ordered document IDs
                'fake_price_group_id',
            ],
            [
                'b2c_en', // catalog ID.
                ['manufacture_location' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'manufacture_location: {lte: 350}', // filter.
                'entity_id', // document data identifier.
                ['1', '6', '7', '8', '9', '11', '12', '13'], // expected ordered document IDs
                'fake_price_group_id',
            ],
            [
                'b2c_en', // catalog ID.
                ['manufacture_location' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'manufacture_location: {gte: 350}', // filter.
                'entity_id', // document data identifier.
                ['5', '2', '3', '4'], // expected ordered document IDs
                'fake_price_group_id',
            ],
            [
                'b2c_en', // catalog ID.
                ['manufacture_location' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'manufacture_location: {eq: "350-*"}', // filter.
                'entity_id', // document data identifier.
                ['5', '2', '3', '4'], // expected ordered document IDs
                'fake_price_group_id',
            ],
            [
                'b2c_en', // catalog ID.
                ['manufacture_location' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'manufacture_location: {lte: 400}', // filter.
                'entity_id', // document data identifier.
                ['1', '6', '7', '8', '9', '11', '12', '13', '2', '3'], // expected ordered document IDs
                'fake_price_group_id',
                '44.832196, -0.554729',
            ],
            [
                'b2c_en', // catalog ID.
                ['manufacture_location' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'manufacture_location: {eq: "*-400"}', // filter.
                'entity_id', // document data identifier.
                ['1', '6', '7', '8', '9', '11', '12', '13', '2', '3'], // expected ordered document IDs
                'fake_price_group_id',
                '44.832196, -0.554729',
            ],
            [
                'b2c_en', // catalog ID.
                ['manufacture_location' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'manufacture_location: {eq: "350-400"}', // filter.
                'entity_id', // document data identifier.
                ['2', '3', '4'], // expected ordered document IDs
                'fake_price_group_id',
                '44.832196, -0.554729',
            ],
            [
                'b2c_en', // catalog ID.
                ['manufacture_location' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'manufacture_location: {in: ["350-400"]}', // filter.
                'entity_id', // document data identifier.
                ['2', '3', '4'], // expected ordered document IDs
                'fake_price_group_id',
                '44.832196, -0.554729',
            ],
            [
                'b2c_en', // catalog ID.
                ['manufacture_location' => SortOrderInterface::SORT_ASC], // sort order specifications.
                'manufacture_location: {in: ["350-500", "600-*"]}', // filter.
                'entity_id', // document data identifier.
                ['2', '3', '4', '5'], // expected ordered document IDs
                'fake_price_group_id',
                '44.832196, -0.554729',
            ],
        ];
    }

    /**
     * @dataProvider filteredWithCategorySearchDocumentsProvider
     *
     * @param string $catalogId             Catalog ID or code
     * @param string $categoryId            Category id to search in
     * @param array  $sortOrders            Sort order specifications
     * @param string $documentIdentifier    Document identifier to check ordered results
     * @param array  $expectedOrderedDocIds Expected ordered document identifiers
     */
    public function testFilteredWithCategorySearchProducts(
        string $catalogId,
        array $sortOrders,
        string $categoryId,
        string $documentIdentifier,
        array $expectedOrderedDocIds
    ): void {
        $user = $this->getUser(Role::ROLE_CONTRIBUTOR);
        $arguments = \sprintf(
            'requestType: product_catalog, localizedCatalog: "%s", pageSize: %d, currentPage: %d, currentCategoryId: "%s"',
            $catalogId,
            10,
            1,
            $categoryId
        );

        $this->addSortOrders($sortOrders, $arguments);

        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                        products: {$this->graphQlQuery}({$arguments}) {
                            collection { id source }
                        }
                    }
                GQL,
                $user
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use ($documentIdentifier, $expectedOrderedDocIds) {
                    $this->validateExpectedResults($response, $documentIdentifier, $expectedOrderedDocIds);
                }
            )
        );
    }

    public function filteredWithCategorySearchDocumentsProvider(): array
    {
        return [
            [
                'b2c_fr', // catalog ID.
                [], // sort order specifications.
                'cat_1', // current category id.
                'entity_id', // document data identifier.
                ['p_02', '1'], // expected ordered document IDs
            ],
            [
                'b2c_fr', // catalog ID.
                [], // sort order specifications.
                'cat_2', // current category id.
                'entity_id', // document data identifier.
                ['1'], // expected ordered document IDs
            ],
        ];
    }

    /**
     * @dataProvider searchWithAggregationDataProvider
     *
     * @param string      $requestType          Request Type
     * @param string      $catalogId            Catalog ID or code
     * @param string|null $categoryId           Category id to search in
     * @param int         $pageSize             Pagination size
     * @param int         $currentPage          Current page
     * @param array|null  $expectedAggregations Expected aggregations sample
     * @param string      $priceGroupId         Price group id
     * @param string|null $query                Query text
     */
    public function testSearchProductsWithAggregation(
        string $requestType,
        string $catalogId,
        ?string $categoryId,
        int $pageSize,
        int $currentPage,
        ?array $expectedAggregations,
        string $priceGroupId = '0',
        ?string $query = null,
        ?string $referenceLocation = null,
    ): void {
        $user = $this->getUser(Role::ROLE_CONTRIBUTOR);

        $arguments = \sprintf(
            'requestType: %s, localizedCatalog: "%s", pageSize: %d, currentPage: %d',
            $requestType,
            $catalogId,
            $pageSize,
            $currentPage
        );

        if ($categoryId) {
            $arguments .= ", currentCategoryId: \"$categoryId\"";
        }

        if (null !== $query) {
            $arguments .= \sprintf(', search: "%s"', $query);
        }

        $headers = [PriceGroupProvider::PRICE_GROUP_ID => $priceGroupId];
        if ($referenceLocation) {
            $headers[ReferenceLocationProvider::REFERENCE_LOCATION] = $referenceLocation;
        }

        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                        products: {$this->graphQlQuery}({$arguments}) {
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
                              options {
                                label
                                value
                                count
                              }
                              date_format
                              hasMore
                            }
                        }
                    }
                GQL,
                $user,
                $headers
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use ($expectedAggregations) {
                    // Extra test on response structure because all exceptions might not throw an HTTP error code.
                    $this->assertJsonContains([
                        'data' => [
                            'products' => [
                                'aggregations' => $expectedAggregations,
                            ],
                        ],
                    ]);
                    $responseData = $response->toArray();
                    if (\is_array($expectedAggregations)) {
                        $this->assertIsArray($responseData['data']['products']['aggregations']);
                        foreach ($responseData['data']['products']['aggregations'] as $data) {
                            $this->assertArrayHasKey('field', $data);
                            $this->assertArrayHasKey('count', $data);
                            $this->assertArrayHasKey('label', $data);
                            $this->assertArrayHasKey('options', $data);
                        }
                    } else {
                        $this->assertNull($responseData['data']['products']['aggregations']);
                    }
                }
            )
        );
    }

    public function searchWithAggregationDataProvider(): array
    {
        return [
            [
                'product_catalog',
                'b2c_en',   // catalog ID.
                null, // Current category id.
                10,     // page size.
                1,      // current page.
                [       // expected aggregations sample.
                    ['field' => 'is_eco_friendly', 'label' => 'Is_eco_friendly', 'type' => 'boolean'],
                    ['field' => 'weight', 'label' => 'Weight', 'type' => 'slider'],
                    ['field' => 'size', 'label' => 'Size', 'type' => 'slider'],
                    [
                        'field' => 'category__id',
                        'label' => 'Category',
                        'type' => 'category',
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
                    [
                        'field' => 'manufacture_location',
                        'label' => 'Manufacture_location',
                        'type' => 'histogram',
                        'options' => [
                            [
                                'label' => '200.0km and more',
                                'value' => '200.0-*',
                                'count' => 12,
                            ],
                        ],
                    ],
                    [
                        'field' => 'tags__value',
                        'label' => 'Tags',
                        'type' => 'checkbox',
                        'hasMore' => false,
                        'options' => [
                            [
                                'label' => '10',
                                'value' => '2',
                                'count' => 1,
                            ],
                            [
                                'label' => '20',
                                'value' => '3',
                                'count' => 1,
                            ],
                            [
                                'label' => '101',
                                'value' => '1',
                                'count' => 2,
                            ],
                            [
                                'label' => '111',
                                'value' => '6',
                                'count' => 1,
                            ],
                            [
                                'label' => '1011',
                                'value' => '5',
                                'count' => 1,
                            ],
                            [
                                'label' => '1012',
                                'value' => '4',
                                'count' => 1,
                            ],
                        ],
                    ],
                    [
                        'field' => 'color__value',
                        'label' => 'Color',
                        'type' => 'checkbox',
                        'options' => [
                            [
                                'label' => 'Black',
                                'value' => 'black',
                                'count' => 10,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'product_catalog',
                'b2c_en',   // catalog ID.
                'cat_1', // Current category id.
                10,     // page size.
                1,      // current page.
                [       // expected aggregations sample.
                    ['field' => 'is_eco_friendly', 'label' => 'Is_eco_friendly', 'type' => 'boolean'],
                    ['field' => 'weight', 'label' => 'Weight', 'type' => 'slider'],
                    [
                        'field' => 'created_at',
                        'label' => 'Created_at',
                        'type' => 'date_histogram',
                        'date_format' => 'yyyy-MM',
                        'options' => [
                            [
                                'label' => '2022-09',
                                'value' => '2022-09',
                                'count' => 2,
                            ],
                        ],
                    ],
                    [
                        'field' => 'category__id',
                        'label' => 'Category',
                        'type' => 'category',
                        'options' => [
                            [
                                'label' => 'Three',
                                'value' => 'cat_3',
                                'count' => 2,
                            ],
                        ],
                    ],
                    [
                        'field' => 'manufacture_location',
                        'label' => 'Manufacture_location',
                        'type' => 'histogram',
                        'options' => [
                            [
                                'label' => '200.0km and more',
                                'value' => '200.0-*',
                                'count' => 2,
                            ],
                        ],
                    ],
                    [
                        'field' => 'tags__value',
                        'label' => 'Tags',
                        'type' => 'checkbox',
                        'hasMore' => false,
                        'options' => [
                            [
                                'label' => '101',
                                'value' => '1',
                                'count' => 2,
                            ],
                            [
                                'label' => '20',
                                'value' => '3',
                                'count' => 1,
                            ],
                            [
                                'label' => '10',
                                'value' => '2',
                                'count' => 1,
                            ],
                        ],
                    ],
                    [
                        'field' => 'color__value',
                        'label' => 'Color',
                        'type' => 'checkbox',
                        'options' => [
                            [
                                'label' => 'Black',
                                'value' => 'black',
                                'count' => 2,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'product_catalog',
                'b2c_en',   // catalog ID.
                'cat_5', // Current category id.
                10,     // page size.
                1,      // current page.
                [       // expected aggregations sample.
                    ['field' => 'is_eco_friendly', 'label' => 'Is_eco_friendly', 'type' => 'boolean'],
                    ['field' => 'weight', 'label' => 'Weight', 'type' => 'slider'],
                    ['field' => 'size', 'label' => 'Size', 'type' => 'slider'],
                    [
                        'field' => 'created_at',
                        'label' => 'Created_at',
                        'type' => 'date_histogram',
                        'date_format' => 'yyyy-MM',
                        'options' => [
                            [
                                'label' => '2022-09',
                                'value' => '2022-09',
                                'count' => 1,
                            ],
                        ],
                    ],
                    [
                        'field' => 'manufacture_location',
                        'label' => 'Manufacture_location',
                        'type' => 'histogram',
                        'options' => [
                            [
                                'label' => '200.0km and more',
                                'value' => '200.0-*',
                                'count' => 1,
                            ],
                        ],
                    ],
                    ['field' => 'brand__value', 'label' => 'Brand', 'type' => 'checkbox'],
                    [
                        'field' => 'tags__value',
                        'label' => 'Tags',
                        'type' => 'checkbox',
                        'hasMore' => false,
                        'options' => [
                            [
                                'label' => '10',
                                'value' => '2',
                                'count' => 1,
                            ],
                            [
                                'label' => '101',
                                'value' => '1',
                                'count' => 1,
                            ],
                        ],
                    ],
                    [
                        'field' => 'color__value',
                        'label' => 'Color',
                        'type' => 'checkbox',
                        'options' => [
                            [
                                'label' => 'Black',
                                'value' => 'black',
                                'count' => 1,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'product_catalog',
                'b2c_fr',   // catalog ID.
                null,   // Current category id.
                10,     // page size.
                1,      // current page.
                [       // expected aggregations sample.
                    ['field' => 'is_eco_friendly', 'label' => 'Is_eco_friendly', 'type' => 'boolean'],
                    ['field' => 'weight', 'label' => 'Weight', 'type' => 'slider'],
                    [
                        'field' => 'size',
                        'label' => 'Taille',
                        'type' => 'slider',
                    ],
                    [
                        'field' => 'category__id',
                        'label' => 'Category',
                        'type' => 'category',
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
                        'field' => 'manufacture_location',
                        'label' => 'Manufacture_location',
                        'type' => 'histogram',
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
                        'options' => [
                            [
                                'label' => 'Noir',
                                'value' => 'black',
                                'count' => 9,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'test_search_query',
                'b2c_fr',   // catalog ID.
                null,   // Current category id.
                10,     // page size.
                1,      // current page.
                null,   // expected aggregations sample.
            ],
            [
                'product_catalog',
                'b2c_fr',   // catalog ID.
                'cat_1', // Current category id.
                10,     // page size.
                1,      // current page.
                [       // expected aggregations sample.
                    ['field' => 'is_eco_friendly', 'label' => 'Is_eco_friendly', 'type' => 'boolean'],
                    ['field' => 'weight', 'label' => 'Weight', 'type' => 'slider'],
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
                        'field' => 'created_at',
                        'label' => 'Created_at',
                        'type' => 'date_histogram',
                        'date_format' => 'yyyy-MM',
                        'options' => [
                            [
                                'label' => '2022-09',
                                'value' => '2022-09',
                                'count' => 2,
                            ],
                        ],
                    ],
                    ['field' => 'category__id', 'label' => 'Category', 'type' => 'category'],
                    [
                        'field' => 'manufacture_location',
                        'label' => 'Manufacture_location',
                        'type' => 'histogram',
                        'options' => [
                            [
                                'label' => 'Plus de 200.0km',
                                'value' => '200.0-*',
                                'count' => 2,
                            ],
                        ],
                    ],
                    ['field' => 'color__value', 'label' => 'Couleur', 'type' => 'checkbox'],
                ],
                '0',
            ],
            [
                'product_catalog',
                'b2c_fr',   // catalog ID.
                'cat_1', // Current category id.
                10,     // page size.
                1,      // current page.
                [       // expected aggregations sample.
                    ['field' => 'is_eco_friendly', 'label' => 'Is_eco_friendly', 'type' => 'boolean'],
                    ['field' => 'weight', 'label' => 'Weight', 'type' => 'slider'],
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
                        'field' => 'created_at',
                        'label' => 'Created_at',
                        'type' => 'date_histogram',
                        'date_format' => 'yyyy-MM',
                        'options' => [
                            [
                                'label' => '2022-09',
                                'value' => '2022-09',
                                'count' => 2,
                            ],
                        ],
                    ],
                    ['field' => 'category__id', 'label' => 'Category', 'type' => 'category'],
                    [
                        'field' => 'manufacture_location',
                        'label' => 'Manufacture_location',
                        'type' => 'histogram',
                        'options' => [
                            [
                                'label' => 'Plus de 200.0km',
                                'value' => '200.0-*',
                                'count' => 2,
                            ],
                        ],
                    ],
                    ['field' => 'color__value', 'label' => 'Couleur', 'type' => 'checkbox'],
                ],
                '1',
            ],
            [ // Test autocomplete aggregations
                'product_catalog',
                'b2c_fr',   // catalog ID.
                'cat_1', // Current category id.
                10,     // page size.
                1,      // current page.
                [       // expected aggregations sample.
                    ['field' => 'is_eco_friendly', 'label' => 'Is_eco_friendly', 'type' => 'boolean'],
                    ['field' => 'weight', 'label' => 'Weight', 'type' => 'slider'],
                    [
                        'field' => 'created_at',
                        'label' => 'Created_at',
                        'type' => 'date_histogram',
                        'date_format' => 'yyyy-MM',
                        'options' => [
                            [
                                'label' => '2022-09',
                                'value' => '2022-09',
                                'count' => 2,
                            ],
                        ],
                    ],
                    ['field' => 'category__id', 'label' => 'Category', 'type' => 'category'],
                    [
                        'field' => 'manufacture_location',
                        'label' => 'Manufacture_location',
                        'type' => 'histogram',
                        'options' => [
                            [
                                'label' => 'Plus de 200.0km',
                                'value' => '200.0-*',
                                'count' => 2,
                            ],
                        ],
                    ],
                    ['field' => 'color__value', 'label' => 'Couleur', 'type' => 'checkbox'],
                ],
                'fake_price_group_id',
            ],
            [
                'product_autocomplete',
                'b2c_en',   // catalog ID.
                null, // Current category id.
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
                    ['field' => 'weight', 'label' => 'Weight', 'type' => 'slider', 'hasMore' => false],
                    ['field' => 'is_eco_friendly', 'label' => 'Is_eco_friendly', 'type' => 'boolean', 'hasMore' => false],
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
                ],
                '0',
                'bag',
            ],
            [ // Test autocomplete aggregations
                'product_search',
                'b2c_fr',   // catalog ID.
                'cat_1', // Current category id.
                10,     // page size.
                1,      // current page.
                [       // expected aggregations sample.
                    ['field' => 'is_eco_friendly', 'label' => 'Is_eco_friendly', 'type' => 'boolean'],
                    ['field' => 'weight', 'label' => 'Weight', 'type' => 'slider'],
                    [
                        'field' => 'created_at',
                        'label' => 'Created_at',
                        'type' => 'date_histogram',
                        'date_format' => 'yyyy-MM',
                        'options' => [
                            [
                                'label' => '2022-09',
                                'value' => '2022-09',
                                'count' => 2,
                            ],
                        ],
                    ],
                    ['field' => 'category__id', 'label' => 'Category', 'type' => 'category'],
                    [
                        'field' => 'manufacture_location',
                        'label' => 'Manufacture_location',
                        'type' => 'histogram',
                        'options' => [
                            [
                                'label' => 'Moins de 1.0km',
                                'value' => '*-1.0',
                                'count' => 1,
                            ],
                            [
                                'label' => 'Plus de 200.0km',
                                'value' => '200.0-*',
                                'count' => 1,
                            ],
                        ],
                    ],
                    ['field' => 'color__value', 'label' => 'Couleur', 'type' => 'checkbox'],
                ],
                'fake_price_group_id',
                null,
                '47.2030827,-1.553246',
            ],
        ];
    }

    /**
     * @dataProvider searchWithAggregationAndFilterDataProvider
     *
     * @param string      $catalogId            Catalog ID or code
     * @param int         $pageSize             Pagination size
     * @param int         $currentPage          Current page
     * @param string|null $filter               Filters to apply
     * @param array       $expectedOptionsCount expected aggregation option count
     */
    public function testSearchProductsWithAggregationAndFilter(
        string $catalogId,
        int $pageSize,
        int $currentPage,
        ?string $filter,
        array $expectedOptionsCount,
    ): void {
        $user = $this->getUser(Role::ROLE_CONTRIBUTOR);

        $arguments = \sprintf(
            'requestType: product_catalog, localizedCatalog: "%s", pageSize: %d, currentPage: %d',
            $catalogId,
            $pageSize,
            $currentPage,
        );
        if ($filter) {
            $arguments = \sprintf(
                'requestType: product_catalog, localizedCatalog: "%s", pageSize: %d, currentPage: %d, filter: [%s]',
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
                        products: {$this->graphQlQuery}({$arguments}) {
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
                    $this->assertIsArray($responseData['data']['products']['aggregations']);
                    foreach ($responseData['data']['products']['aggregations'] as $data) {
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
                'b2c_en',   // catalog ID.
                10,     // page size.
                1,      // current page.
                null, // filter.
                [ // expected option result
                    'color__value' => 9,
                    'category__id' => 2,
                    'is_eco_friendly' => 2,
                ],
            ],
            [
                'b2c_en',   // catalog ID.
                10,     // page size.
                1,      // current page.
                '{sku: {eq: "24-WB05"}}', // filter.
                [ // expected option result
                    'color__value' => 1,
                    'category__id' => 0,
                    'is_eco_friendly' => 1,
                ],
            ],
            [
                'b2c_en',   // catalog ID.
                10,     // page size.
                1,      // current page.
                '{color__value: {in: ["pink"]}}', // filter.
                [ // expected option result
                    'color__value' => 9,
                    'category__id' => 0,
                    'is_eco_friendly' => 1,
                ],
            ],
        ];
    }

    private function addSortOrders(array $sortOrders, string &$arguments): void
    {
        if (!empty($sortOrders)) {
            $sortArguments = [];
            foreach ($sortOrders as $field => $direction) {
                $sortArguments[] = \sprintf('%s : %s', $field, $direction);
            }
            $arguments .= \sprintf(', sort: {%s}', implode(', ', $sortArguments));
        }
    }

    /**
     * Validate result in search products response.
     *
     * @param ResponseInterface $response              Api response to validate
     * @param string            $documentIdentifier    Document identifier to check ordered results
     * @param array             $expectedOrderedDocIds Expected ordered document identifiers
     */
    private function validateExpectedResults(ResponseInterface $response, string $documentIdentifier, array $expectedOrderedDocIds): void
    {
        // Extra test on response structure because all exceptions might not throw an HTTP error code.
        $this->assertJsonContains([
            'data' => [
                'products' => [
                    'collection' => [],
                ],
            ],
        ]);

        $responseData = $response->toArray();
        $this->assertIsArray($responseData['data']['products']['collection']);
        $this->assertCount(\count($expectedOrderedDocIds), $responseData['data']['products']['collection']);
        foreach ($responseData['data']['products']['collection'] as $index => $document) {
            $this->assertArrayHasKey('id', $document);
            $this->assertEquals($this->getUri('products', $expectedOrderedDocIds[$index]), $document['id']);

            $this->assertArrayHasKey('source', $document);
            if (\array_key_exists($documentIdentifier, $document['source'])) {
                $this->assertEquals($expectedOrderedDocIds[$index], $document['source'][$documentIdentifier]);
            }
        }
    }
}
