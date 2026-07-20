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

use Gally\Test\ExpectedResponse;
use Gally\Test\RequestGraphQlToTest;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Reusable GraphQL product search assertions, for any test that needs to check what a real search
 * returns rather than what the database holds — a thesaurus being applied, an imported source field
 * showing up as a facet, and so on.
 *
 * Usable from a plain API test and from a job test alike: it only needs validateApiCall() and
 * getApiRoutePrefix() from Gally\Test\AbstractTestCase. A job test must still create the index and
 * load its document fixtures (see AbstractTestCase::createEntityElasticsearchIndices() and
 * loadElasticsearchDocumentFixtures()).
 */
trait ProductSearchAssertionTrait
{
    /**
     * The building block every other method here is written on top of: run one `products()` search
     * and hand the decoded `data.products` payload to $assertProducts. Pass whatever GraphQL
     * selection set the assertion needs — `collection`, `aggregations`, `paginationInfo`, … — so a
     * new kind of check does not need a new query method.
     *
     * @param string   $selectionSet   GraphQL fields to select inside `products(...)`
     * @param callable $assertProducts receives `data.products` as an array
     */
    protected function assertProductSearch(
        string $localizedCatalogId,
        string $selectionSet,
        callable $assertProducts,
        ?string $queryText = null,
        string $requestType = 'product_search',
        ?int $pageSize = 10,
        ?int $currentPage = 1,
        ?string $currentCategoryId = null,
    ): void {
        $arguments = \sprintf(
            'requestType: %s, localizedCatalog: "%s"',
            $requestType,
            $localizedCatalogId
        );

        if (null !== $queryText) {
            $arguments .= \sprintf(', search: "%s"', $queryText);
        }
        if (null !== $pageSize) {
            $arguments .= \sprintf(', pageSize: %d', $pageSize);
        }
        if (null !== $currentPage) {
            $arguments .= \sprintf(', currentPage: %d', $currentPage);
        }
        // product_catalog fails without it; product_search accepts it as an extra restriction.
        if (null !== $currentCategoryId) {
            $arguments .= \sprintf(', currentCategoryId: "%s"', $currentCategoryId);
        }

        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                        products({$arguments}) {
                            {$selectionSet}
                        }
                    }
                GQL,
                null
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use ($assertProducts) {
                    $responseData = $response->toArray();
                    // GraphQL reports business errors with a 200, so this has to be checked here
                    // rather than left to the status code.
                    $this->assertArrayNotHasKey(
                        'errors',
                        $responseData,
                        isset($responseData['errors']) ? json_encode($responseData['errors']) : ''
                    );

                    $assertProducts($responseData['data']['products']);
                }
            )
        );
    }

    /**
     * Assert the exact ordered list of product ids a fulltext search returns.
     */
    protected function assertProductSearchResults(
        string $localizedCatalogId,
        string $queryText,
        array $expectedProductIds,
        string $requestType = 'product_search',
        int $pageSize = 10,
        int $currentPage = 1,
    ): void {
        $this->assertProductSearch(
            $localizedCatalogId,
            <<<GQL
                collection {
                  id
                  name
                }
                paginationInfo {
                  itemsPerPage
                  lastPage
                  totalCount
                }
            GQL,
            function (array $products) use ($expectedProductIds) {
                $productIds = array_map(
                    fn ($productId) => str_replace($this->getApiRoutePrefix() . 'products/', '', $productId),
                    array_column($products['collection'], 'id')
                );
                $this->assertEquals($expectedProductIds, $productIds);
            },
            $queryText,
            $requestType,
            $pageSize,
            $currentPage,
        );
    }

    /**
     * The aggregations a real search returns, keyed by field name — which is what a shopper's facet
     * list is built from. Returning them instead of asserting lets the caller check presence,
     * absence, option lists or truncation without a method per case.
     *
     * @return array<string, array{field: string, count: int|null, label: string|null, type: string|null, options: ?array, hasMore: bool|null}>
     */
    protected function fetchProductAggregations(
        string $localizedCatalogId,
        ?string $queryText = null,
        string $requestType = 'product_search',
        ?string $currentCategoryId = null,
    ): array {
        $aggregations = [];

        $this->assertProductSearch(
            $localizedCatalogId,
            <<<GQL
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
                  hasMore
                }
            GQL,
            function (array $products) use (&$aggregations) {
                foreach ($products['aggregations'] ?? [] as $aggregation) {
                    $aggregations[$aggregation['field']] = $aggregation;
                }
            },
            $queryText,
            $requestType,
            null,
            null,
            $currentCategoryId,
        );

        return $aggregations;
    }
}
