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

namespace Gally\Search\Tests\Api\GraphQl;

use Gally\Test\AbstractTestCase;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestGraphQlToTest;
use Gally\User\Constant\Role;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ViewMoreFacetTest extends AbstractTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::loadFixture([
            __DIR__ . '/../../fixtures/facet_configuration.yaml',
            __DIR__ . '/../../fixtures/source_field_option_label.yaml',
            __DIR__ . '/../../fixtures/source_field_option.yaml',
            __DIR__ . '/../../fixtures/source_field_label.yaml',
            __DIR__ . '/../../fixtures/source_field.yaml',
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
     * @dataProvider viewMoreOptionDataProvider
     *
     * @param string  $entityType         Entity Type
     * @param string  $catalogId          Catalog ID or code
     * @param string  $aggregation        Aggregation
     * @param ?string $expectedError      Expected error
     * @param ?int    $expectedItemsCount Expected items count in (paged) response
     * @param string  $filter             Filters to apply
     * @param ?string $optionSearch       filter option result matching search
     */
    public function testViewMoreFacetOptions(
        string $entityType,
        string $catalogId,
        string $aggregation,
        ?string $expectedError,
        ?int $expectedItemsCount,
        string $filter,
        ?string $optionSearch,
    ): void {
        $user = $this->getUser(Role::ROLE_CONTRIBUTOR);

        $arguments = \sprintf(
            'entityType: "%s", localizedCatalog: "%s", aggregation: "%s", filter: [%s]',
            $entityType,
            $catalogId,
            $aggregation,
            $filter
        );

        if ($optionSearch) {
            $arguments .= \sprintf(', optionSearch: "%s"', $optionSearch);
        }

        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                        viewMoreFacetOptions({$arguments}) {
                            value
                            label
                            count
                        }
                    }
                GQL,
                $user
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use ($expectedError, $expectedItemsCount) {
                    if (!empty($expectedError)) {
                        $this->assertGraphQlError($expectedError);
                        $this->assertJsonContains(['data' => ['viewMoreFacetOptions' => null]]);
                    } else {
                        $responseData = $response->toArray();
                        $this->assertIsArray($responseData['data']['viewMoreFacetOptions']);
                        $this->assertCount($expectedItemsCount, $responseData['data']['viewMoreFacetOptions']);
                        foreach ($responseData['data']['viewMoreFacetOptions'] as $data) {
                            $this->assertArrayHasKey('value', $data);
                            $this->assertArrayHasKey('label', $data);
                            $this->assertArrayHasKey('count', $data);
                        }
                    }
                }
            )
        );
    }

    public function viewMoreOptionDataProvider(): array
    {
        return [
            [
                'product_document', // entity type.
                'b2c_en', // catalog ID.
                'invalid_field', // aggregation.
                'The source field \'invalid_field\' does not exist', // expected error.
                null, // expected items count.
                '', // filter.
                null, // option search.
            ],
            [
                'product_document', // entity type.
                'b2c_en', // catalog ID.
                'size', // aggregation.
                null, // expected error.
                9, // expected items count.
                '', // filter.
                null, // option search.
            ],
            [
                'product_document', // entity type.
                'b2c_en', // catalog ID.
                'category__id', // aggregation.
                null, // expected error.
                2, // expected items count.
                '', // filter.
                null, // option search.
            ],
            [
                'product_document', // entity type.
                'b2c_en', // catalog ID.
                'size', // aggregation.
                null, // expected error.
                1, // expected items count.
                '{equalFilter: {field:"sku", eq: "24-MB01"}}', // filter.
                null, // option search.
            ],
            [
                'product_document', // entity type.
                'b2c_en', // catalog ID.
                'color__value', // aggregation.
                null, // expected error.
                2, // expected items count.
                '', // filter.
                'gre', // option search.
            ],
        ];
    }
}
