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

namespace Gally\Configuration\Tests\Api;

use Gally\User\Constant\Role;

trait ConfigurationGetCollectionTrait
{
    protected function getFilteredCollectionDataProvider(): iterable
    {
        $user = $this->getUser(Role::ROLE_CONTRIBUTOR);

        // No value in db, value should be retrieved from file.
        yield [
            null,                   // Default user
            'gally.fake_path', // Config path
            null,                   // Locale code
            null,                   // Request type
            null,                   // Localized catalog
            null,                   // Page size
            null,                   // Current page
            401,                    // Expected response code
            [],                     // Expected value
        ];

        // No value in db, value should be retrieved from file.
        yield [
            $this->getUser(Role::ROLE_ADMIN), // Default user
            'gally.fake_path', // Config path
            null,                   // Locale code
            null,                   // Request type
            null,                   // Localized catalog
            null,                   // Page size
            null,                   // Current page
            200,                    // Expected response code
            [],                     // Expected value
        ];

        // No value in db, value should be retrieved from file.
        yield [
            $user,                  // Default user
            'gally.fake_path', // Config path
            null,                   // Locale code
            null,                   // Request type
            null,                   // Localized catalog
            null,                   // Page size
            null,                   // Current page
            200,                    // Expected response code
            [],                     // Expected value
        ];

        // No value in db, value should be retrieved from file.
        yield [
            $user,                  // Default user
            'gally.indices_settings.prefix', // Config path
            null,                   // Locale code
            null,                   // Request type
            null,                   // Localized catalog
            null,                   // Page size
            null,                   // Current page
            200,                    // Expected response code
            [                       // Expected value
                [
                    'path' => 'gally.indices_settings.prefix',
                    'value' => 'gally_test__gally',
                ],
            ],
        ];

        // A value exist in db, it should override file value.
        yield [
            $user,                  // Default user
            'gally.base_url.media',  // Config path
            null,                   // Locale code
            null,                   // Request type
            null,                   // Localized catalog
            null,                   // Page size
            null,                   // Current page
            200,                    // Expected response code
            [                       // Expected value
                [
                    'path' => 'gally.base_url.media',
                    'value' => 'test_value_general',
                ],
            ],
        ];

        // No value defined for given locale, it should fallback on default value.
        yield [
            $user,                  // Default user
            'gally.base_url.media',  // Config path
            'en_US',                // Locale code
            null,                   // Request type
            null,                   // Localized catalog
            null,                   // Page size
            null,                   // Current page
            200,                    // Expected response code
            [                       // Expected value
                [
                    'path' => 'gally.base_url.media',
                    'value' => 'test_value_general',
                ],
            ],
        ];

        // A value is defined for given locale, it should return this value.
        yield [
            $user,                  // Default user
            'gally.base_url.media',  // Config path
            'fr_FR',                // Locale code
            null,                   // Request type
            null,                   // Localized catalog
            null,                   // Page size
            null,                   // Current page
            200,                    // Expected response code
            [                       // Expected value
                [
                    'path' => 'gally.base_url.media',
                    'value' => 'test_value_fr',
                ],
            ],
        ];

        // No value defined for given request type, it should fallback on default value.
        yield [
            $user,                  // Default user
            'gally.base_url.media',  // Config path
            null,                   // Locale code
            'product_search',       // Request type
            null,                   // Localized catalog
            null,                   // Page size
            null,                   // Current page
            200,                    // Expected response code
            [                       // Expected value
                [
                    'path' => 'gally.base_url.media',
                    'value' => 'test_value_general',
                ],
            ],
        ];

        // A value is defined for given request type, it should return this value.
        yield [
            $user,                  // Default user
            'gally.base_url.media',  // Config path
            null,                   // Locale code
            'product_catalog',      // Request type
            null,                   // Localized catalog
            null,                   // Page size
            null,                   // Current page
            200,                    // Expected response code
            [                       // Expected value
                [
                    'path' => 'gally.base_url.media',
                    'value' => 'test_value_product_catalog',
                ],
            ],
        ];

        // No value defined for given localized catalog, it should fallback on default value.
        yield [
            $user,                  // Default user
            'gally.base_url.media',  // Config path
            null,                   // Locale code
            null,                   // Request type
            'b2c',                  // Localized catalog
            null,                   // Page size
            null,                   // Current page
            200,                    // Expected response code
            [                       // Expected value
                [
                    'path' => 'gally.base_url.media',
                    'value' => 'test_value_general',
                ],
            ],
        ];

        // A value is defined for given localized catalog, it should return this value.
        yield [
            $user,                  // Default user
            'gally.base_url.media',  // Config path
            null,                   // Locale code
            null,                   // Request type
            'b2b',                  // Localized catalog
            null,                   // Page size
            null,                   // Current page
            200,                    // Expected response code
            [                       // Expected value
                [
                    'path' => 'gally.base_url.media',
                    'value' => 'test_value_b2b',
                ],
            ],
        ];

        // Test priority between context
        yield [
            $user,                  // Default user
            'gally.base_url.media',  // Config path
            'fr_FR',                // Locale code
            'product_catalog',      // Request type
            'b2b',                  // Localized catalog
            null,                   // Page size
            null,                   // Current page
            200,                    // Expected response code
            [                       // Expected value
                [
                    'path' => 'gally.base_url.media',
                    'value' => 'test_value_b2b',
                ],
            ],
        ];

        // Test priority between context
        yield [
            $user,                  // Default user
            'gally.base_url.media',  // Config path
            'fr_FR',                // Locale code
            'product_catalog',      // Request type
            'b2c',                  // Localized catalog
            null,                   // Page size
            null,                   // Current page
            200,                    // Expected response code
            [                       // Expected value
                [
                    'path' => 'gally.base_url.media',
                    'value' => 'test_value_product_catalog',
                ],
            ],
        ];

        // Test priority between context
        yield [
            $user,                  // Default user
            'gally.base_url.media',  // Config path
            'fr_FR',                // Locale code
            'product_search',       // Request type
            'b2c',                  // Localized catalog
            null,                   // Page size
            null,                   // Current page
            200,                    // Expected response code
            [                       // Expected value
                [
                    'path' => 'gally.base_url.media',
                    'value' => 'test_value_fr',
                ],
            ],
        ];

        // Test priority between context
        yield [
            $user,                  // Default user
            'gally.base_url.media',  // Config path
            'en_US',                // Locale code
            'product_search',       // Request type
            'b2c',                  // Localized catalog
            null,                   // Page size
            null,                   // Current page
            200,                    // Expected response code
            [                       // Expected value
                [
                    'path' => 'gally.base_url.media',
                    'value' => 'test_value_general',
                ],
            ],
        ];

        // Test partial path
        yield [
            $user,                  // Default user
            'gally.search_settings',  // Config path
            'en_Us',                // Locale code
            'product_search',       // Request type
            'b2c',                  // Localized catalog
            null,                   // Page size
            null,                   // Current page
            200,                    // Expected response code
            [                       // Expected value
                [
                    'path' => 'gally.search_settings.default_date_field_format',
                    'value' => 'yyyy-MM',
                ],
                [
                    'path' => 'gally.search_settings.default_distance_unit',
                    'value' => 'km',
                ],
                [
                    'path' => 'gally.search_settings.aggregations.coverage_use_indexed_fields_property',
                    'value' => false,
                ],
                [
                    'path' => 'gally.search_settings.aggregations.default_date_range_interval',
                    'value' => '1M',
                ],
                [
                    'path' => 'gally.search_settings.aggregations.default_distance_ranges',
                    'value' => [
                        ['to' => 1],
                        ['from' => 1, 'to' => 5],
                        ['from' => 5, 'to' => 10],
                        ['from' => 10, 'to' => 20],
                        ['from' => 20, 'to' => 30],
                        ['from' => 30, 'to' => 50],
                        ['from' => 50, 'to' => 100],
                        ['from' => 100, 'to' => 200],
                        ['from' => 200],
                    ],
                ],
                [
                    'path' => 'gally.search_settings.sort.default_asc_sort_field',
                    'value' => ['_count', '_score'],
                ],
            ],
        ];

        // Test pagination
        yield [
            $user,                  // Default user
            'gally.search_settings',  // Config path
            'en_Us',                // Locale code
            'product_search',       // Request type
            'b2c',                  // Localized catalog
            3,                      // Page size
            1,                      // Current page
            200,                    // Expected response code
            [                       // Expected value
                [
                    'path' => 'gally.search_settings.default_date_field_format',
                    'value' => 'yyyy-MM',
                ],
                [
                    'path' => 'gally.search_settings.default_distance_unit',
                    'value' => 'km',
                ],
                [
                    'path' => 'gally.search_settings.aggregations.coverage_use_indexed_fields_property',
                    'value' => false,
                ],
            ],
        ];

        // Test pagination
        yield [
            $user,                  // Default user
            'gally.search_settings',  // Config path
            'en_Us',                // Locale code
            'product_search',       // Request type
            'b2c',                  // Localized catalog
            3,                      // Page size
            2,                      // Current page
            200,                    // Expected response code
            [                       // Expected value
                [
                    'path' => 'gally.search_settings.aggregations.default_date_range_interval',
                    'value' => '1M',
                ],
                [
                    'path' => 'gally.search_settings.aggregations.default_distance_ranges',
                    'value' => [
                        ['to' => 1],
                        ['from' => 1, 'to' => 5],
                        ['from' => 5, 'to' => 10],
                        ['from' => 10, 'to' => 20],
                        ['from' => 20, 'to' => 30],
                        ['from' => 30, 'to' => 50],
                        ['from' => 50, 'to' => 100],
                        ['from' => 100, 'to' => 200],
                        ['from' => 200],
                    ],
                ],
                [
                    'path' => 'gally.search_settings.sort.default_asc_sort_field',
                    'value' => ['_count', '_score'],
                ],
            ],
        ];

        // Test get value type from symfony treeBuilder
        yield [
            $user,                  // Default user
            'gally.graphql_query_renaming', // Config path
            null,                  // Locale code
            null,                  // Request type
            null,                  // Localized catalog
            null,                  // Page size
            null,                  // Current page
            200,                    // Expected response code
            [                      // Expected value
                [
                    'path' => 'gally.graphql_query_renaming',
                    'value' => [
                        'Gally\VectorSearch\Entity\Document' => ['renamings' => ['vectorSearchVectorDocuments' => 'vectorSearchDocuments']],
                        'Gally\Explain\Entity\ExplainProduct' => ['renamings' => ['explainExplainProducts' => 'explain']],
                        'Gally\Boost\Entity\BoostPreview' => ['renamings' => ['getBoostPreview' => 'previewBoost']],
                        'Gally\Search\Entity\Document' => ['renamings' => ['searchDocuments' => 'documents']],
                        'Gally\Search\Entity\Source\SortingOption' => ['renamings' => ['getSortingOptions' => 'sortingOptions']],
                        'Gally\Product\Entity\Product' => [
                            'renamings' => [
                                'searchProducts' => 'products',
                                'searchPreviewProducts' => 'previewProducts',
                            ],
                        ],
                        'Gally\Category\Entity\Source\CategorySortingOption' => ['renamings' => ['getCategorySortingOptions' => 'categorySortingOptions']],
                    ],
                ],
            ],
        ];

        // Test no path
        yield [
            $user,                  // Default user
            null,                  // Config path
            null,                  // Locale code
            null,                  // Request type
            null,                  // Localized catalog
            null,                  // Page size
            null,                  // Current page
            200,                    // Expected response code
            [                      // Expected value
                [
                    'path' => 'gally.menu',
                    'value' => [
                        'catalog' => ['order' => 10],
                        'product' => ['parent' => 'catalog', 'order' => 20],
                        'category' => ['parent' => 'catalog', 'order' => 10],
                        'thesaurus' => ['order' => 30, 'path' => '/thesaurus/grid'],
                        'optimizer' => ['order' => 20, 'css_class' => 'boost', 'path' => '/boost/grid'],
                    ],
                ],
            ],
        ];
    }
}
