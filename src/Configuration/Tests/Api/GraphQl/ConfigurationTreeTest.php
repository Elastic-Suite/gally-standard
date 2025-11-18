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

use Gally\Test\ExpectedResponse;
use Gally\Test\RequestGraphQlToTest;
use Gally\User\Entity\User;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ConfigurationTreeTest extends \Gally\Configuration\Tests\Api\Rest\ConfigurationTreeTest
{
    public static function setUpBeforeClass(): void
    {
        static::loadFixture([
            __DIR__ . '/../../fixtures/configurations.yaml',
            __DIR__ . '/../../fixtures/catalogs.yaml',
        ]);
    }

    /**
     * @dataProvider configurationTreeDataProvider
     */
    public function testConfigurationTree(
        ?User $user,
        int $expectedResponseCode,
        array $expectedConfigurationTree,
    ): void {
        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                      configurationTree { configTree }
                    }
                GQL,
                $user
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use ($expectedConfigurationTree, $expectedResponseCode) {
                    if (401 == $expectedResponseCode) {
                        $this->assertGraphQlError('Access Denied.');
                    } else {
                        $data = $response->toArray();
                        $this->assertJsonContains(
                            ['data' => ['configurationTree' => ['configTree' => $expectedConfigurationTree]]],
                            true,
                            $data['errors'][0]['message'] ?? ''
                        );
                    }
                }
            )
        );
    }
}
