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

namespace Gally\Configuration\Tests\Api\GraphQl;

use Gally\Configuration\Tests\Api\ConfigurationGetCollectionTrait;
use Gally\Test\AbstractTestCase;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestGraphQlToTest;
use Gally\User\Entity\User;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ConfigurationTest extends AbstractTestCase
{
    use ConfigurationGetCollectionTrait;

    public static function setUpBeforeClass(): void
    {
        static::loadFixture([
            __DIR__ . '/../../fixtures/configurations.yaml',
            __DIR__ . '/../../fixtures/catalogs.yaml',
        ]);
    }

    /**
     * @dataProvider getFilteredCollectionDataProvider
     */
    public function testFilteredGetCollection(
        ?User $user,
        string|array|null $path,
        ?string $language,
        ?string $localCode,
        ?string $requestType,
        ?string $localizedCatalogCode,
        ?int $pageSize,
        ?int $currentPage,
        int $expectedResponseCode,
        array $expectedConfigurations,
    ): void {
        $filters = \is_array($path)
            ? 'path_list: ' . json_encode($path)
            : 'path: "' . $path . '"';

        if (null !== $language) {
            $filters .= ', language: "' . $language . '"';
        }
        if (null !== $localCode) {
            $filters .= ', localeCode: "' . $localCode . '"';
        }
        if (null !== $requestType) {
            $filters .= ', requestType: "' . $requestType . '"';
        }
        if (null !== $localizedCatalogCode) {
            $filters .= ', localizedCatalogCode: "' . $localizedCatalogCode . '"';
        }
        if (null !== $pageSize) {
            $filters .= ', pageSize: ' . $pageSize;
        }
        if (null !== $currentPage) {
            $filters .= ', currentPage: ' . $currentPage;
        }

        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                      configurations($filters) {
                        collection { id, path, value }
                      }
                    }
                GQL,
                $user
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use ($expectedConfigurations, $expectedResponseCode) {
                    if (401 == $expectedResponseCode) {
                        $this->assertGraphQlError('Access Denied.');
                    } else {
                        $data = $response->toArray();
                        $expectedConfigurations = array_map(
                            function (array $configuration) {
                                $configuration['value'] = json_encode($configuration['value']);

                                return $configuration;
                            },
                            $expectedConfigurations
                        );
                        $this->assertJsonContains(
                            ['data' => ['configurations' => ['collection' => $expectedConfigurations]]],
                            true,
                            $data['errors'][0]['message'] ?? ''
                        );
                    }
                }
            )
        );
    }
}
