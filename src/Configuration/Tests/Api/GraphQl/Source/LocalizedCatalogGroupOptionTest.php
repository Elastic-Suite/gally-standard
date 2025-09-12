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

namespace Gally\Configuration\Tests\Api\GraphQl\Source;

use Gally\Test\AbstractTestCase;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestGraphQlToTest;
use Gally\User\Constant\Role;
use Symfony\Contracts\HttpClient\ResponseInterface;

class LocalizedCatalogGroupOptionTest extends AbstractTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::loadFixture([
            __DIR__ . '/../../../fixtures/catalogs.yaml',
        ]);
    }

    /**
     * @dataProvider getCollectionDataProvider
     */
    public function testGetCollection(array $expectedData): void
    {
        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                      configurationLocalizedCatalogGroupOptions {
                        value
                        label
                        options
                      }
                    }
                GQL,
                $this->getUser(Role::ROLE_CONTRIBUTOR),
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use ($expectedData) {
                    $responseData = $response->toArray();
                    $this->assertSame($expectedData, $responseData['data']['configurationLocalizedCatalogGroupOptions']);
                }
            )
        );
    }

    public function getCollectionDataProvider(): array
    {
        return [
            [
                [
                    [
                        'value' => 'general',
                        'label' => 'General',
                        'options' => [
                            ['value' => null, 'label' => 'All localized catalogs'],
                        ],
                    ],
                    [
                        'value' => 'b2c_test',
                        'label' => 'B2C Test Catalog',
                        'options' => [
                            ['value' => $this->getUri('localized_catalogs', '1'), 'label' => 'B2C French Test Store View'],
                            ['value' => $this->getUri('localized_catalogs', '2'), 'label' => 'B2C English Test Store View'],
                        ],
                    ],
                    [
                        'value' => 'b2b_test',
                        'label' => 'B2B Test Catalog',
                        'options' => [],
                    ],
                ],
            ],
        ];
    }
}
