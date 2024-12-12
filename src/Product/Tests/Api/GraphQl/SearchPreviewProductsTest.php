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

use Gally\User\Constant\Role;

class SearchPreviewProductsTest extends SearchProductsTest
{
    protected string $graphQlQuery = 'previewProducts';

    public function securityDataProvider(): array
    {
        return [
            [$this->getUser(Role::ROLE_ADMIN), null],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), null],
            [null, 'Access Denied.'],
        ];
    }

    public function sortedSearchProductsProvider(): array
    {
        return [
            [
                'b2c_fr',   // catalog ID.
                10,     // page size.
                1,      // current page.
                [],     // sort order specifications.
                'entity_id', // document data identifier.
                ['1', 'p_02'],    // expected ordered document IDs
                '0', // Price group
                'cat_1', // Current category id
                // test products are sorted by 'created_at' because defaultSorting in current category configuration is 'created_at' (defaultSorting for cat_1 in fixtures is 'name').
                json_encode([
                    '@context' => $this->getRoute('contexts/CategoryConfiguration'),
                    '@type' => 'CategoryConfiguration',
                    'category' => $this->getUri('categories', 'cat_1'),
                    'name' => 'Un',
                    'useNameInProductSearch' => false,
                    'defaultSorting' => 'created_at',
                    'isActive' => true,
                ]), // Current category configuration
            ],
            ...parent::sortedSearchProductsProvider(),
        ];
    }
}
