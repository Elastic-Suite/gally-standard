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
        // No value in db, value should be retrieved from file.
        yield [
            null,                   // Default user
            'gally.fake_path', // Config path
            null,                   // Language code
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
            null,                   // Language code
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
            $this->getUser(Role::ROLE_CONTRIBUTOR), // Default user
            'gally.fake_path', // Config path
            null,                   // Language code
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
            $this->getUser(Role::ROLE_CONTRIBUTOR), // Default user
            'gally.indices_settings.prefix', // Config path
            null,                   // Language code
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
            $this->getUser(Role::ROLE_CONTRIBUTOR), // Default user
            'gally.base_url.media', // Config path
            null,                   // Language code
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
            $this->getUser(Role::ROLE_CONTRIBUTOR), // Default user
            'gally.base_url.media', // Config path
            'en',                   // Language code
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
            $this->getUser(Role::ROLE_CONTRIBUTOR), // Default user
            'gally.base_url.media', // Config path
            null,                   // Language code
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
            $this->getUser(Role::ROLE_CONTRIBUTOR), // Default user
            'gally.base_url.media', // Config path
            'fr',                   // Language code
            null,                   // Locale code
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

        // A value is defined for given locale, it should return this value.
        yield [
            $this->getUser(Role::ROLE_CONTRIBUTOR), // Default user
            'gally.base_url.media', // Config path
            null,                   // Language code
            'fr_FR',                // Locale code
            null,                   // Request type
            null,                   // Localized catalog
            null,                   // Page size
            null,                   // Current page
            200,                    // Expected response code
            [                       // Expected value
                [
                    'path' => 'gally.base_url.media',
                    'value' => 'test_value_fr_FR',
                ],
            ],
        ];

        // No value defined for given request type, it should fallback on default value.
        yield [
            $this->getUser(Role::ROLE_CONTRIBUTOR), // Default user
            'gally.base_url.media', // Config path
            null,                   // Language code
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
            $this->getUser(Role::ROLE_CONTRIBUTOR), // Default user
            'gally.base_url.media', // Config path
            null,                   // Language code
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
            $this->getUser(Role::ROLE_CONTRIBUTOR), // Default user
            'gally.base_url.media', // Config path
            null,                   // Language code
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
            $this->getUser(Role::ROLE_CONTRIBUTOR), // Default user
            'gally.base_url.media', // Config path
            null,                   // Language code
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
            $this->getUser(Role::ROLE_CONTRIBUTOR), // Default user
            'gally.base_url.media', // Config path
            'fr',                   // Language code
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
            $this->getUser(Role::ROLE_CONTRIBUTOR), // Default user
            'gally.base_url.media', // Config path
            'fr',                   // Language code
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
            $this->getUser(Role::ROLE_CONTRIBUTOR), // Default user
            'gally.base_url.media', // Config path
            'fr',                   // Language code
            'fr_FR',                // Locale code
            'product_search',       // Request type
            'b2c',                  // Localized catalog
            null,                   // Page size
            null,                   // Current page
            200,                    // Expected response code
            [                       // Expected value
                [
                    'path' => 'gally.base_url.media',
                    'value' => 'test_value_fr_FR',
                ],
            ],
        ];

        // Test priority between context
        yield [
            $this->getUser(Role::ROLE_CONTRIBUTOR), // Default user
            'gally.base_url.media', // Config path
            'fr',                   // Language code
            'fr_CA',                // Locale code
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
            $this->getUser(Role::ROLE_CONTRIBUTOR), // Default user
            'gally.base_url.media', // Config path
            'en',                   // Language code
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
            $this->getUser(Role::ROLE_CONTRIBUTOR), // Default user
            'gally.search_settings', // Config path
            'en',                   // Language code
            'en_US',                // Locale code
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
                    'path' => 'gally.search_settings.sort.default_desc_sort_field',
                    'value' => ['_count', '_score'],
                ],
            ],
        ];

        // Test pagination
        yield [
            $this->getUser(Role::ROLE_CONTRIBUTOR), // Default user
            'gally.search_settings', // Config path
            'en',                   // Language code
            'en_US',                // Locale code
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
            $this->getUser(Role::ROLE_CONTRIBUTOR), // Default user
            'gally.search_settings', // Config path
            'en',                   // Language code
            'en_US',                // Locale code
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
                    'path' => 'gally.search_settings.sort.default_desc_sort_field',
                    'value' => ['_count', '_score'],
                ],
            ],
        ];

        // Test get value type from symfony treeBuilder
        yield [
            $this->getUser(Role::ROLE_CONTRIBUTOR), // Default user
            'gally.analysis.char_filters', // Config path
            null,                  // Language code
            null,                  // Locale code
            null,                  // Request type
            null,                  // Localized catalog
            null,                  // Page size
            null,                  // Current page
            200,                   // Expected response code
            [                      // Expected value
                [
                    'path' => 'gally.analysis.char_filters',
                    'value' => [
                        'html_strip' => ['type' => 'html_strip'],
                    ],
                ],
            ],
        ];

        // Test no path
        yield [
            $this->getUser(Role::ROLE_CONTRIBUTOR), // Default user
            null,                  // Config path
            null,                  // Language code
            null,                  // Locale code
            null,                  // Request type
            null,                  // Localized catalog
            null,                  // Page size
            null,                  // Current page
            200,                   // Expected response code
            [                      // Expected value
                [
                    'path' => 'gally.analysis.char_filters',
                    'value' => [
                        'html_strip' => ['type' => 'html_strip'],
                    ],
                ],
                [
                    'path' => 'gally.analysis.filters',
                    'value' => [
                        'trim' => ['type' => 'trim'],
                        'truncate_to_max' => ['type' => 'truncate', 'params' => ['length' => 8192]],
                        'lowercase' => ['type' => 'lowercase'],
                        'word_delimiter' => [
                            'type' => 'word_delimiter',
                            'params' => [
                                'generate_word_parts' => true,
                                'catenate_words' => true,
                                'catenate_numbers' => true,
                                'catenate_all' => true,
                                'split_on_case_change' => true,
                                'split_on_numerics' => true,
                                'preserve_original' => true,
                            ],
                        ],
                        'shingle' => ['type' => 'shingle', 'params' => ['min_shingle_size' => 2, 'max_shingle_size' => 2, 'output_unigrams' => true]],
                        'reference_shingle' => [
                            'type' => 'shingle',
                            'params' => ['min_shingle_size' => 2, 'max_shingle_size' => 10, 'output_unigrams' => true, 'token_separator' => ''],
                        ],
                        'reference_word_delimiter' => [
                            'type' => 'word_delimiter',
                            'params' => [
                                'generate_word_parts' => true,
                                'catenate_words' => false,
                                'catenate_numbers' => false,
                                'catenate_all' => false,
                                'split_on_case_change' => true,
                                'split_on_numerics' => true,
                                'preserve_original' => false,
                            ],
                        ],
                        'ascii_folding' => ['type' => 'asciifolding', 'params' => ['preserve_original' => false]],
                        'phonetic' => ['type' => 'phonetic', 'params' => ['encoder' => 'metaphone']],
                        'edge_ngram_filter' => ['type' => 'edge_ngram', 'params' => ['min_gram' => 3, 'max_gram' => 20]],
                    ],
                ],
            ],
        ];

        // Test multiple paths
        yield [
            $this->getUser(Role::ROLE_CONTRIBUTOR), // Default user
            [                      // Config path
                'gally.analysis.char_filters',
                'gally.base_url.media',
            ],
            null,                  // Language code
            null,                  // Locale code
            null,                  // Request type
            null,                  // Localized catalog
            null,                  // Page size
            null,                  // Current page
            200,                   // Expected response code
            [                      // Expected value
                [
                    'path' => 'gally.analysis.char_filters',
                    'value' => [
                        'html_strip' => ['type' => 'html_strip'],
                    ],
                ],
                [
                    'path' => 'gally.base_url.media',
                    'value' => 'test_value_general',
                ],
            ],
        ];
    }
}
