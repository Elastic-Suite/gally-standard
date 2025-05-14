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

namespace Gally\Configuration\Tests\Api\Rest;

use Gally\Configuration\Entity\Configuration;
use Gally\Configuration\Repository\ConfigurationRepository;
use Gally\Configuration\Tests\Api\ConfigurationGetCollectionTrait;
use Gally\Test\AbstractEntityTestWithUpdate;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestToTest;
use Gally\User\Constant\Role;
use Gally\User\Entity\User;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ConfigurationTest extends AbstractEntityTestWithUpdate
{
    use ConfigurationGetCollectionTrait;

    protected static function getFixtureFiles(): array
    {
        return [
            __DIR__ . '/../../fixtures/configurations.yaml',
            __DIR__ . '/../../fixtures/catalogs.yaml',
        ];
    }

    protected function getEntityClass(): string
    {
        return Configuration::class;
    }

    /**
     * @depends testFilteredGetCollection
     *
     * @dataProvider createDataProvider
     */
    public function testCreate(
        ?User $user,
        array $data,
        int $responseCode = 201,
        ?string $message = null,
        ?string $validRegex = null
    ): void {
        parent::testCreate($user, $data, $responseCode, $message, $validRegex);
    }

    public function createDataProvider(): iterable
    {
        $user = $this->getUser(Role::ROLE_ADMIN);

        yield [
            null,
            [
                'path' => 'gally.base_url.media',
                'value' => 'Test value api',
                'scopeType' => Configuration::SCOPE_GENERAL,
                'scopeCode' => null,
            ],
            401,
        ];
        yield [
            $this->getUser(Role::ROLE_CONTRIBUTOR),
            [
                'path' => 'gally.base_url.media',
                'value' => 'Test value api',
                'scopeType' => Configuration::SCOPE_GENERAL,
                'scopeCode' => null,
            ],
            403,
        ];
        yield [
            $user,
            [
                'path' => 'gally.base_url.media',
                'value' => 'Test value api',
                'scopeType' => 'FakeScopeType',
                'scopeCode' => null,
            ],
            400,
            'Invalid scope type : "FakeScopeType".',
        ];
        yield [
            $user,
            [
                'path' => 'gally.base_url.media',
                'value' => 'Test value api',
                'scopeType' => Configuration::SCOPE_GENERAL,
                'scopeCode' => 'fake_scope_code',
            ],
            400,
            'Invalid scope code "fake_scope_code" for scope "general".',
        ];
        yield [
            $user,
            [
                'path' => 'gally.base_url.media',
                'value' => 'Test value api',
                'scopeType' => Configuration::SCOPE_LOCALE,
                'scopeCode' => 'fake_locale_code',
            ],
            400,
            'Invalid scope code "fake_locale_code" for scope "locale".',
        ];
        yield [
            $user,
            [
                'path' => 'gally.base_url.media',
                'value' => 'Test value api',
                'scopeType' => Configuration::SCOPE_LANGUAGE,
                'scopeCode' => 'fake_language_code',
            ],
            400,
            'Invalid scope code "fake_language_code" for scope "language".',
        ];
        yield [
            $user,
            [
                'path' => 'gally.base_url.media',
                'value' => 'Test value api',
                'scopeType' => Configuration::SCOPE_REQUEST_TYPE,
                'scopeCode' => 'fake_request_type',
            ],
            400,
            'Invalid scope code "fake_request_type" for scope "request_type".',
        ];
        yield [
            $user,
            [
                'path' => 'gally.base_url.media',
                'value' => 'Test value api',
                'scopeType' => Configuration::SCOPE_LOCALIZED_CATALOG,
                'scopeCode' => 'fake_localized_catalog_code',
            ],
            400,
            'Invalid scope code "fake_localized_catalog_code" for scope "localized_catalog".',
        ];
        yield [
            $user,
            [
                'path' => 'gally.base_url.media',
                'value' => 'Test value api',
                'scopeType' => Configuration::SCOPE_GENERAL,
                'scopeCode' => null,
            ],
            500,
            'An exception occurred while executing a query: SQLSTATE[23505]: Unique violation: 7 ERROR:  duplicate key value violates unique constraint "unique_path_scope_null"
DETAIL:  Key (path, scope_type)=(gally.base_url.media, general) already exists.',
        ];
        yield [
            $user,
            [
                'path' => 'gally.base_url.media',
                'value' => 'Test value api by locale general',
                'scopeType' => Configuration::SCOPE_LOCALE,
                'scopeCode' => null,
            ],
            201,
        ];
        yield [
            $user,
            [
                'path' => 'gally.base_url.media',
                'value' => 'Test value api by locale es_ES',
                'scopeType' => Configuration::SCOPE_LOCALE,
                'scopeCode' => 'es_ES',
            ],
            201,
        ];
        yield [
            $user,
            [
                'path' => 'gally.base_url.media',
                'value' => 'Test value api by request type general',
                'scopeType' => Configuration::SCOPE_REQUEST_TYPE,
                'scopeCode' => null,
            ],
            201,
        ];
        yield [
            $user,
            [
                'path' => 'gally.base_url.media',
                'value' => 'Test value api by request type product_search',
                'scopeType' => Configuration::SCOPE_REQUEST_TYPE,
                'scopeCode' => 'product_autocomplete',
            ],
            201,
        ];
        yield [
            $user,
            [
                'path' => 'gally.base_url.media',
                'value' => 'Test value api by localized catalog general',
                'scopeType' => Configuration::SCOPE_LOCALIZED_CATALOG,
                'scopeCode' => null,
            ],
            201,
        ];
        yield [
            $user,
            [
                'path' => 'gally.base_url.media',
                'value' => 'Test value api by localized catalog b2b',
                'scopeType' => Configuration::SCOPE_LOCALIZED_CATALOG,
                'scopeCode' => 'b2c_test_fr',
            ],
            201,
        ];
        yield [
            $user,
            [
                'path' => 'gally.base_url.media',
                'value' => 'Test value api by localized catalog b2b',
                'scopeType' => Configuration::SCOPE_LOCALIZED_CATALOG,
                'scopeCode' => 'b2c_test_fr',
            ],
            500,
            'An exception occurred while executing a query: SQLSTATE[23505]: Unique violation: 7 ERROR:  duplicate key value violates unique constraint "unique_path_scope"
DETAIL:  Key (path, scope_type, scope_code)=(gally.base_url.media, localized_catalog, b2c_test_fr) already exists.',
        ];
    }

    protected function getJsonCreationValidation(array $expectedData): array
    {
        $expectedData['value'] = json_encode($expectedData['value']);

        return $expectedData;
    }

    public function getDataProvider(): iterable
    {
        yield [
            null,
            1,
            [
                'id' => 1,
                'path' => 'gally.base_url.media',
                'scopeType' => 'general',
                'scopeCode' => null,
            ],
            200,
        ];
        yield [
            $this->getUser(Role::ROLE_CONTRIBUTOR),
            1,
            [
                'id' => 1,
                'path' => 'gally.base_url.media',
                'scopeType' => 'general',
                'scopeCode' => null,
            ],
            200,
        ];
        yield [
            $this->getUser(Role::ROLE_ADMIN),
            2,
            [
                'id' => 2,
                'path' => 'gally.base_url.media',
                'scopeType' => 'localized_catalog',
                'scopeCode' => 'b2b',
            ],
            200,
        ];
        yield [$this->getUser(Role::ROLE_ADMIN), 666, [], 404];
    }

    public function deleteDataProvider(): iterable
    {
        $adminUser = $this->getUser(Role::ROLE_ADMIN);

        yield [null, 1, 401];
        yield [$this->getUser(Role::ROLE_CONTRIBUTOR), 1, 403];
        yield [$adminUser, 1, 204];
        yield [$adminUser, 666, 404];
    }

    public function patchUpdateDataProvider(): iterable
    {
        yield [null, 1, ['value' => 'Value updated'], 405];
    }

    protected function getJsonUpdateValidation(array $expectedData): array
    {
        $expectedData = parent::getJsonCreationValidation($expectedData);
        $expectedData['value'] = json_encode($expectedData['value']);

        return $expectedData;
    }

    public function putUpdateDataProvider(): iterable
    {
        yield [null, 7, ['value' => 'Test value api by locale es_ES updated'], 401];
        yield [$this->getUser(Role::ROLE_CONTRIBUTOR), 7, ['value' => 'Test value api by locale es_ES updated'], 403];
        yield [$this->getUser(Role::ROLE_ADMIN), 7, ['value' => 'Test value api by locale es_ES updated'], 200];
    }

    public function getCollectionDataProvider(): iterable
    {
        yield [null, 40, 200];
        yield [$this->getUser(Role::ROLE_CONTRIBUTOR), 40, 200];
        yield [$this->getUser(Role::ROLE_ADMIN), 40, 200];
    }

    /**
     * @dataProvider getFilteredCollectionDataProvider
     */
    public function testFilteredGetCollection(
        ?User $user,
        ?string $path,
        ?string $language,
        ?string $localCode,
        ?string $requestType,
        ?string $localizedCatalogCode,
        ?int $pageSize,
        ?int $currentPage,
        int $expectedResponseCode,
        array $expectedConfigurations,
    ): void {
        $data = array_filter([
            'path' => $path,
            'language' => $language,
            'localeCode' => $localCode,
            'requestType' => $requestType,
            'localizedCatalogCode' => $localizedCatalogCode,
            'pageSize' => $pageSize,
            'currentPage' => $currentPage,
        ]);
        $query = http_build_query($data);
        $this->validateApiCall(
            new RequestToTest('GET', 'configurations' . ($query ? '?' . $query : ''), $user),
            new ExpectedResponse(
                $expectedResponseCode,
                function (ResponseInterface $response) use ($expectedConfigurations) {
                    $this->assertJsonContains(['hydra:member' => $expectedConfigurations]);
                }
            )
        );
    }

    /**
     * @depends testPutUpdate
     *
     * @dataProvider bulkDataProvider
     */
    public function testBulk(
        ?User $user,
        array $configurations,
        int $expectedConfigurationsNumber,
        array $expectedResponseData,
        array $expectedSearchValues,
        int $responseCode,
        ?string $message = null
    ): void {
        $request = new RequestToTest('POST', "{$this->getApiPath()}/bulk", $user, $configurations);
        $expectedResponse = new ExpectedResponse(
            $responseCode,
            function (ResponseInterface $response) use ($configurations, $expectedConfigurationsNumber, $expectedResponseData, $expectedSearchValues) {
                $this->assertJsonContains(['hydra:member' => $expectedResponseData]);
                $configurationRepository = static::getContainer()->get(ConfigurationRepository::class);
                $existingConfigurations = $configurationRepository->findAll();
                $this->assertCount($expectedConfigurationsNumber, $existingConfigurations);
                foreach ($configurations as $configurationData) {
                    $configuration = $configurationRepository->findOneBy(
                        [
                            'path' => $configurationData['path'],
                            'scopeType' => $configurationData['scope_type'],
                            'scopeCode' => $configurationData['scope_code'] ?? null,
                        ]
                    );
                    $this->assertSame(
                        $expectedSearchValues[
                            implode(
                                '_',
                                array_filter([$configurationData['path'], $configurationData['scope_code'] ?? null])
                            )
                        ],
                        $configuration->getDecodedValue()
                    );
                }
            },
            $message
        );

        $this->validateApiCall($request, $expectedResponse);
    }

    protected function bulkDataProvider(): iterable
    {
        $adminUser = $this->getUser(Role::ROLE_ADMIN);

        // Test ACL
        yield [null, [], 11, [], [], 401];
        yield [$this->getUser(Role::ROLE_CONTRIBUTOR), [], 11, [], [], 403];

        // Incomplete / invalid data
        yield [
            $adminUser, // Api User
            [   // Source field post data
                ['value' => 'test'],
                ['path' => 'gally.test_conf', 'value' => 'test'],
                ['path' => 'gally.test_conf', 'value' => 'test', 'scope_type' => 'general'],
            ],
            12, // Expected configurations number
            [], // Expected data in response
            [], // Expected search values
            400, // Expected response code
            // Expected error messages
            'Option #0: Path is required for configuration. ' .
            'Option #1: Scope type is required for configuration.',
        ];
        yield [
            $adminUser, // Api User
            [   // Source field post data
                ['path' => 'gally.test_conf', 'value' => 'test', 'scope_type' => 'general', 'scope_code' => 'fake'],
                ['path' => 'gally.test_conf', 'value' => 'test', 'scope_type' => 'language', 'scope_code' => 'fake'],
                ['path' => 'gally.test_conf', 'value' => 'test', 'scope_type' => 'locale', 'scope_code' => 'fake'],
                ['path' => 'gally.test_conf', 'value' => 'test', 'scope_type' => 'request_type', 'scope_code' => 'fake'],
                ['path' => 'gally.test_conf', 'value' => 'test', 'scope_type' => 'localized_catalog', 'scope_code' => 'fake'],
            ],
            12, // Expected configurations number
            [], // Expected data in response
            [], // Expected search values
            400, // Expected response code
            // Expected error messages
            'Option #0: Invalid scope code "fake" for scope "general". ' .
            'Option #1: Invalid scope code "fake" for scope "language". ' .
            'Option #2: Invalid scope code "fake" for scope "locale". ' .
            'Option #3: Invalid scope code "fake" for scope "request_type". ' .
            'Option #4: Invalid scope code "fake" for scope "localized_catalog".',
        ];

        yield [
            $adminUser, // Api User
            [   // Source field post data
                ['path' => 'gally.test_conf', 'value' => 'bulk value', 'scope_type' => 'general'],
            ],
            12, // Expected configurations number
            [], // Expected data in response
            [   // Expected values
                'gally.test_conf' => 'bulk value',
            ],
            201, // Expected response code
        ];
        yield [
            $adminUser, // Api User
            [   // Source field post data
                ['path' => 'gally.test_conf', 'value' => 'bulk value updated', 'scope_type' => 'general'],
                ['path' => 'gally.test_conf', 'value' => 'bulk value', 'scope_type' => 'language', 'scope_code' => 'fr'],
                ['path' => 'gally.test_conf', 'value' => 'bulk value', 'scope_type' => 'locale', 'scope_code' => 'fr_FR'],
                ['path' => 'gally.test_conf2', 'value' => ['test_array' => 'test'], 'scope_type' => 'locale', 'scope_code' => 'fr_FR'],
            ],
            15, // Expected configurations number
            [], // Expected data in response
            [   // Expected values
                'gally.test_conf' => 'bulk value updated',
                'gally.test_conf_fr' => 'bulk value',
                'gally.test_conf_fr_FR' => 'bulk value',
                'gally.test_conf2_fr_FR' => ['test_array' => 'test'],
            ],
            201, // Expected response code
        ];
    }
}
