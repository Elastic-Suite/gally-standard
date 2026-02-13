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

namespace Gally\Index\Tests\Api\GraphQl;

use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Index\Api\IndexSettingsInterface;
use Gally\Index\Entity\Index;
use Gally\Index\Repository\Index\IndexRepositoryInterface;
use Gally\Test\AbstractTestCase;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestGraphQlToTest;
use Gally\User\Constant\Role;
use Gally\User\Entity\User;
use OpenSearch\Client;
use Symfony\Contracts\HttpClient\ResponseInterface;

class IndexOperationsTest extends AbstractTestCase
{
    private static IndexRepositoryInterface $indexRepository;

    private static IndexSettingsInterface $indexSettings;

    private static Client $client;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        \assert(static::getContainer()->get(IndexRepositoryInterface::class) instanceof IndexRepositoryInterface);
        self::$indexRepository = static::getContainer()->get(IndexRepositoryInterface::class);
        self::loadFixture([
            __DIR__ . '/../../fixtures/catalogs.yaml',
            __DIR__ . '/../../fixtures/source_field.yaml',
            __DIR__ . '/../../fixtures/metadata.yaml',
        ]);
        self::$indexSettings = static::getContainer()->get(IndexSettingsInterface::class);
        self::$client = static::getContainer()->get('opensearch.client.test'); // @phpstan-ignore-line
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        self::deleteElasticsearchFixtures();
    }

    /**
     * @dataProvider createIndexDataProvider
     */
    public function testCreateIndex(?User $user, string $entityType, int $catalogId, array $expectedData): void
    {
        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    mutation {
                      createIndex(input: {
                        entityType: "{$entityType}",
                        localizedCatalog: "{$catalogId}"
                      }) {
                        index {
                          id
                          name
                          aliases
                        }
                      }
                    }
                GQL,
                $user,
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use ($expectedData) {
                    if (isset($expectedData['error'])) {
                        $this->assertGraphQlError($expectedData['error']);
                    } else {
                        $this->assertStringContainsString('"id":"' . str_replace('/', '\/', $this->getUri('indices', $expectedData['name'])), $response->getContent());
                        $this->assertStringContainsString('"name":"' . $expectedData['name'], $response->getContent());
                        $this->assertStringContainsString('"aliases":[', $response->getContent());
                        foreach ($expectedData['aliases'] as $expectedAlias) {
                            $this->assertStringContainsString('"' . $expectedAlias . '"', $response->getContent());
                        }
                    }
                }
            )
        );
    }

    public function createIndexDataProvider(): iterable
    {
        self::loadFixture([
            __DIR__ . '/../../fixtures/metadata.yaml',
            __DIR__ . '/../../fixtures/catalogs.yaml',
        ]);
        $localizedCatalogRepository = static::getContainer()->get(LocalizedCatalogRepository::class);
        $admin = $this->getUser(Role::ROLE_ADMIN);

        yield [
            null,
            'product',
            1,
            ['error' => 'Access Denied.'],
        ];

        yield [
            $this->getUser(Role::ROLE_CONTRIBUTOR),
            'product',
            1,
            ['error' => 'Access Denied.'],
        ];

        foreach ($localizedCatalogRepository->findAll() as $localizedCatalog) {
            yield [
                $admin,
                'product',
                (int) $localizedCatalog->getId(),
                [
                    'name' => "gally_test__gally_localized_catalog_{$localizedCatalog->getCode()}_product",
                    'aliases' => [
                        '.entity_product',
                        ".catalog_{$localizedCatalog->getCatalog()->getCode()}",
                        ".localized_catalog_{$localizedCatalog->getCode()}",
                        ".locale_{$localizedCatalog->getLocale()}",
                    ],
                ],
            ];
            yield [
                $admin,
                'category',
                (int) $localizedCatalog->getId(),
                [
                    'name' => "gally_test__gally_localized_catalog_{$localizedCatalog->getCode()}_category",
                    'aliases' => [
                        '.entity_category',
                        ".catalog_{$localizedCatalog->getCatalog()->getCode()}",
                        ".localized_catalog_{$localizedCatalog->getCode()}",
                        ".locale_{$localizedCatalog->getLocale()}",
                    ],
                ],
            ];
        }
    }

    /**
     * @depends testCreateIndex
     *
     * @dataProvider installIndexDataProvider
     */
    public function testInstallIndex(?User $user, string $indexNamePrefix, array $expectedData): void
    {
        $installIndexSettings = self::$indexSettings->getInstallIndexSettings();
        $index = self::$indexRepository->findByName("{$indexNamePrefix}*");
        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    mutation {
                      installIndex(input: {
                        name: "{$index->getName()}"
                      }) {
                        index {
                          id
                          name
                          aliases
                        }
                      }
                    }
                GQL,
                $user,
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use ($index, $expectedData, $installIndexSettings) {
                    if (isset($expectedData['error'])) {
                        $this->assertGraphQlError($expectedData['error']);
                    } else {
                        $responseData = $response->toArray();

                        // Check that the index has the install aliases.
                        $this->assertNotEmpty($responseData['data']['installIndex']['index']['aliases']);
                        foreach ($expectedData['aliases'] as $alias) {
                            $this->assertContains(strtolower($alias), $responseData['data']['installIndex']['index']['aliases']);
                        }

                        // Check that the index has the proper installed index settings.
                        $settings = self::$client->indices()->getSettings(['index' => $index->getName()]);
                        $this->assertNotEmpty($settings[$index->getName()]['settings']['index']);
                        $this->assertArraySubset($installIndexSettings, $settings[$index->getName()]['settings']['index']);
                    }
                }
            )
        );
    }

    public function installIndexDataProvider(): iterable
    {
        self::loadFixture([
            __DIR__ . '/../../fixtures/metadata.yaml',
            __DIR__ . '/../../fixtures/catalogs.yaml',
        ]);
        $localizedCatalogRepository = static::getContainer()->get(LocalizedCatalogRepository::class);
        $admin = $this->getUser(Role::ROLE_ADMIN);

        yield [
            null,
            'gally_test__gally_localized_catalog_b2c_fr_product',
            ['error' => 'Access Denied.'],
        ];

        yield [
            $this->getUser(Role::ROLE_CONTRIBUTOR),
            'gally_test__gally_localized_catalog_b2c_fr_product',
            ['error' => 'Access Denied.'],
        ];

        foreach ($localizedCatalogRepository->findAll() as $localizedCatalog) {
            yield [
                $admin,
                "gally_test__gally_localized_catalog_{$localizedCatalog->getCode()}_product",
                [
                    'aliases' => [
                        "gally_test__gally_localized_catalog_{$localizedCatalog->getCode()}_product",
                        "gally_test__gally_catalog_{$localizedCatalog->getCatalog()->getCode()}_product",
                        "gally_test__gally_locale_{$localizedCatalog->getLocale()}_product",
                    ],
                ],
            ];
            yield [
                $admin,
                "gally_test__gally_localized_catalog_{$localizedCatalog->getCode()}_category",
                [
                    'aliases' => [
                        "gally_test__gally_localized_catalog_{$localizedCatalog->getCode()}_category",
                        "gally_test__gally_catalog_{$localizedCatalog->getCatalog()->getCode()}_category",
                        "gally_test__gally_locale_{$localizedCatalog->getLocale()}_category",
                    ],
                ],
            ];
        }
    }

    /**
     * @depends testInstallIndex
     *
     * @dataProvider installIndexDataProvider
     */
    public function testRefreshIndex(?User $user, string $indexNamePrefix, array $expectedData): void
    {
        $index = self::$indexRepository->findByName("{$indexNamePrefix}*");
        $initialRefreshCount = $this->getRefreshCount($index->getName());

        $this->assertNotNull($index);
        $this->assertInstanceOf(Index::class, $index);
        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    mutation {
                      refreshIndex(input: {
                        name: "{$index->getName()}"
                      }) {
                        index {
                          id
                          name
                          aliases
                        }
                      }
                    }
                GQL,
                $user,
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use ($index, $expectedData, $initialRefreshCount) {
                    if (isset($expectedData['error'])) {
                        $this->assertGraphQlError($expectedData['error']);
                    } else {
                        // Check that the index still has the install index.
                        // TODO re-instate tests on aliases when the read stage is correctly performed based on name.
                        // $this->assertNotEmpty($responseData['data']['refreshIndex']['index']['aliases']);
                        // $this->assertContains($expectedInstalledIndexAlias, $responseData['data']['refreshIndex']['index']['aliases']);

                        $refreshCount = $this->getRefreshCount($index->getName());
                        $this->assertGreaterThan($initialRefreshCount, $refreshCount);
                    }
                }
            )
        );
    }

    protected function getRefreshCount(string $indexName): int
    {
        $refreshMetrics = self::$client->indices()->stats(['index' => $indexName, 'metric' => 'refresh']);
        $this->assertNotEmpty($refreshMetrics);
        $this->assertArrayHasKey('_all', $refreshMetrics);
        $this->assertArrayHasKey('primaries', $refreshMetrics['_all']);
        $this->assertArrayHasKey('refresh', $refreshMetrics['_all']['primaries']);
        $this->assertArrayHasKey('external_total', $refreshMetrics['_all']['primaries']['refresh']);

        return $refreshMetrics['_all']['primaries']['refresh']['external_total'];
    }
}
