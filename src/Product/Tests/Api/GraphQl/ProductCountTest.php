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

use Gally\Test\AbstractTestCase;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestGraphQlToTest;
use Gally\User\Constant\Role;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ProductCountTest extends AbstractTestCase
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
     * @dataProvider productCountByCategoryDataProvider
     *
     * @param string $localizedCatalogId Catalog ID or code
     * @param string $search             Current search
     * @param string $filters            Filters to apply
     * @param int    $totalCount         Total product count in all categories
     * @param array  $categoryCount      Product count by categories
     */
    public function testProductCountByCategory(
        string $localizedCatalogId,
        string $search,
        string $filters,
        int $totalCount,
        array $categoryCount
    ): void {
        $arguments = \sprintf(
            'requestType: product_category_count, localizedCatalog: "%s", search: "%s", filter: {%s}, pageSize: 0, currentPage: 1',
            $localizedCatalogId,
            $search,
            $filters
        );

        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                        products: {$this->graphQlQuery}({$arguments}) {
                            aggregations {
                              type
                              field
                              label
                              count
                              options {
                                count
                                label
                                value
                              }
                            }
                        }
                    }
                GQL,
                $this->getUser(Role::ROLE_CONTRIBUTOR)
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use (
                    $totalCount,
                    $categoryCount,
                ) {
                    $data = $response->toArray();
                    $this->assertCount(1, $data['data']['products']['aggregations']);
                    $this->assertJsonContains([
                        'data' => [
                            'products' => [
                                'aggregations' => [
                                    [
                                        'type' => 'category',
                                        'field' => 'category__id',
                                        'label' => 'Category',
                                        'count' => $totalCount,
                                        'options' => $categoryCount,
                                    ],
                                ],
                            ],
                        ],
                    ]);
                }
            )
        );
    }

    public function productCountByCategoryDataProvider(): array
    {
        return [
            [
                'b2c_en',   // localized catalog ID.
                '',         // search
                '',         // filters
                6,          // total product count in all category
                [           // product count by categories
                    [
                        'label' => 'One',
                        'value' => 'cat_1',
                        'count' => 2,
                    ],
                    [
                        'label' => 'Three',
                        'value' => 'cat_3',
                        'count' => 2,
                    ],
                    [
                        'label' => 'Four',
                        'value' => 'cat_4',
                        'count' => 1,
                    ],
                    [
                        'label' => 'Five',
                        'value' => 'cat_5',
                        'count' => 1,
                    ],
                ],
            ],
            [
                'b2c_fr',   // localized catalog ID.
                '',         // search
                '',         // filters
                3,          // total product count in all category
                [           // product count by categories
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
                'b2c_en',   // localized catalog ID.
                'winter',   // search
                '',         // filters
                3,          // total product count in all category
                [           // product count by categories
                    [
                        'label' => 'One',
                        'value' => 'cat_1',
                        'count' => 1,
                    ],
                    [
                        'label' => 'Three',
                        'value' => 'cat_3',
                        'count' => 1,
                    ],
                    [
                        'label' => 'Five',
                        'value' => 'cat_5',
                        'count' => 1,
                    ],
                ],
            ],
            [
                'b2c_en',   // localized catalog ID.
                '',         // search
                'name: { match: "Bag" }',  // filters
                3,          // total product count in all category
                [           // product count by categories
                    [
                        'label' => 'One',
                        'value' => 'cat_1',
                        'count' => 1,
                    ],
                    [
                        'label' => 'Three',
                        'value' => 'cat_3',
                        'count' => 1,
                    ],
                    [
                        'label' => 'Five',
                        'value' => 'cat_5',
                        'count' => 1,
                    ],
                ],
            ],
        ];
    }
}
