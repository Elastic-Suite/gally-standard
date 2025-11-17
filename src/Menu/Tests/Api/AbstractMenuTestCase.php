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

namespace Gally\Menu\Tests\Api;

use Gally\Test\AbstractTestCase;
use Gally\User\Constant\Role;

abstract class AbstractMenuTestCase extends AbstractTestCase
{
    protected function menuDataProvider(): array
    {
        $frMenu = [
            'hierarchy' => [
                [
                    'code' => 'catalog',
                    'label' => 'Catalogue',
                    'order' => 10,
                    'children' => [
                        [
                            'code' => 'category',
                            'label' => 'Catégories',
                            'order' => 10,
                        ],
                        [
                            'code' => 'product',
                            'label' => 'Produits',
                            'order' => 20,
                        ],
                    ],
                ],
                [
                    'code' => 'optimizer',
                    'label' => 'Optimiseur',
                    'order' => 20,
                    'css_class' => 'boost',
                    'path' => '/boost/grid',
                ],
                [
                    'code' => 'thesaurus',
                    'label' => 'Thésaurus',
                    'order' => 30,
                    'path' => '/thesaurus/grid',
                ],
            ],
        ];

        $enMenu = [
            'hierarchy' => [
                [
                    'code' => 'catalog',
                    'label' => 'Catalog',
                    'order' => 10,
                    'children' => [
                        [
                            'code' => 'category',
                            'label' => 'Categories',
                            'order' => 10,
                        ],
                        [
                            'code' => 'product',
                            'label' => 'Products',
                            'order' => 20,
                        ],
                    ],
                ],
                [
                    'code' => 'optimizer',
                    'label' => 'Optimizer',
                    'order' => 20,
                    'css_class' => 'boost',
                    'path' => '/boost/grid',
                ],
                [
                    'code' => 'thesaurus',
                    'label' => 'Thesaurus',
                    'order' => 30,
                    'path' => '/thesaurus/grid',
                ],
            ],
        ];

        $user = $this->getUser(Role::ROLE_CONTRIBUTOR);

        return [
            ['fr', $frMenu, $this->getUser(Role::ROLE_ADMIN)],
            ['fr_FR', $frMenu, $user],
            ['en', $enMenu, $user],
            ['en_US', $enMenu, $user],
        ];
    }
}
