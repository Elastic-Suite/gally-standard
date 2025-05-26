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

trait PublicConfigurationGetCollectionTrait
{
    protected function getFilteredCollectionDataProvider(): iterable
    {
        // A value exist in db, it should override file value.
        yield [
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

        // A value is defined for given locale, it should return this value.
        yield [
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

        // No value defined for given locale, it should fallback on default value.
        yield [
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
    }
}
