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

namespace Gally\Category\Tests\Api\GraphQl;

use Gally\Catalog\Repository\CatalogRepository;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Test\AbstractTestCase;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestGraphQlToTest;
use Gally\User\Constant\Role;
use Gally\User\Entity\User;
use Symfony\Contracts\HttpClient\ResponseInterface;

class CategoryConfigurationTest extends AbstractTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::loadFixture([
            __DIR__ . '/../../fixtures/catalogs.yaml',
            __DIR__ . '/../../fixtures/categories.yaml',
            __DIR__ . '/../../fixtures/configurations.yaml',
            __DIR__ . '/../../fixtures/source_field.yaml',
            __DIR__ . '/../../fixtures/metadata.yaml',
        ]);
    }

    /**
     * @dataProvider getWithContextDataProvider
     */
    public function testGetWithContext(string $categoryId, ?string $catalogCode, ?string $localizedCatalogCode, array $expectedData, ?User $user): void
    {
        $catalogRepository = static::getContainer()->get(CatalogRepository::class);
        $localizedCatalogRepository = static::getContainer()->get(LocalizedCatalogRepository::class);

        $catalogId = $catalogCode
            ? $catalogRepository->findOneBy(['code' => $catalogCode])?->getId()
            : 'null';
        $localizedCatalogId = $localizedCatalogCode
            ? $localizedCatalogRepository->findOneBy(['code' => $localizedCatalogCode])?->getId()
            : 'null';

        // Fake id for invalid catalog
        $catalogId = $catalogId ?: '123456';
        $localizedCatalogId = $localizedCatalogId ?: '123456';

        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                        getCategoryConfiguration(
                          categoryId: "$categoryId"
                          catalogId: {$catalogId}
                          localizedCatalogId: {$localizedCatalogId}
                        ) {
                          name
                          isVirtual
                          defaultSorting
                        }
                  }
                GQL,
                $user,
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use ($expectedData) {
                    if (\array_key_exists('error', $expectedData)) {
                        $this->assertGraphQlError($expectedData['error']);
                    } else {
                        $this->assertJsonContains($expectedData);
                    }
                }
            )
        );
    }

    protected function getWithContextDataProvider(): iterable
    {
        $user = $this->getUser(Role::ROLE_ADMIN);
        yield [
            'fake_category',
            'fake_catalog',
            'fake_localized_catalog',
            ['error' => 'Category with id fake_category not found.'],
            $user,
        ];
        yield [
            'one',
            'fake_catalog',
            'fake_localized_catalog',
            ['error' => 'Catalog with id 123456 not found.'],
            $user,
        ];
        yield [
            'one',
            'b2c',
            'fake_localized_catalog',
            ['error' => 'Localized catalog with id 123456 not found.'],
            $user,
        ];
        yield [
            'one',
            'b2c',
            'b2c_fr',
            ['error' => 'Access Denied.'],
            null,
        ];
        yield [
            'one',
            'b2c',
            'b2c_fr',
            [
                'data' => [
                    'getCategoryConfiguration' => [
                        'name' => 'Un',
                        'isVirtual' => true,
                        'defaultSorting' => 'category__position',
                    ],
                ],
            ],
            $this->getUser(Role::ROLE_CONTRIBUTOR),
        ];
        yield [
            'one',
            'b2c',
            'b2c_en',
            [
                'data' => [
                    'getCategoryConfiguration' => [
                        'name' => 'One',
                        'isVirtual' => false,
                        'defaultSorting' => 'price__price',
                    ],
                ],
            ],
            $user,
        ];
        yield [
            'three',
            'b2c',
            'b2c_fr',
            [
                'data' => [
                    'getCategoryConfiguration' => [
                        'name' => 'Trois',
                        'isVirtual' => false,
                        'defaultSorting' => 'category__position',
                    ],
                ],
            ],
            $user,
        ];
        yield [
            'one',
            'b2c',
            'b2c_en',
            [
                'data' => [
                    'getCategoryConfiguration' => [
                        'name' => 'One',
                        'isVirtual' => false,
                        'defaultSorting' => 'price__price',
                    ],
                ],
            ],
            $user,
        ];
        yield [
            'one',
            'b2c',
            null,
            [
                'data' => [
                    'getCategoryConfiguration' => [
                        'name' => 'Un',
                        'isVirtual' => false,
                        'defaultSorting' => 'category__position',
                    ],
                ],
            ],
            $user,
        ];
        yield [
            'one',
            'b2b',
            null,
            [
                'data' => [
                    'getCategoryConfiguration' => [
                        'name' => 'One',
                        'isVirtual' => true,
                        'defaultSorting' => 'category__position',
                    ],
                ],
            ],
            $user,
        ];
        yield [
            'one',
            null,
            null,
            [
                'data' => [
                    'getCategoryConfiguration' => [
                        'name' => 'Un',
                        'isVirtual' => true,
                        'defaultSorting' => 'category__position',
                    ],
                ],
            ],
            $user,
        ];
        yield [
            'five',
            null,
            null,
            [
                'data' => [
                    'getCategoryConfiguration' => [
                        'name' => 'Five',
                        'isVirtual' => false,
                        'defaultSorting' => 'category__position',
                    ],
                ],
            ],
            $user,
        ];
    }
}
