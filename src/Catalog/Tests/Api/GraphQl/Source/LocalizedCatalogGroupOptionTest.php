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

namespace Gally\Catalog\Tests\Api\GraphQl\Source;

use Gally\Test\AbstractTestCase;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestGraphQlToTest;
use Symfony\Contracts\HttpClient\ResponseInterface;

class LocalizedCatalogGroupOptionTest extends AbstractTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::loadFixture([
            __DIR__ . '/../../../fixtures/localized_catalogs.yaml',
            __DIR__ . '/../../../fixtures/catalogs.yaml',
        ]);
    }

    /**
     * @dataProvider getCollectionDataProvider
     */
    public function testGetCollection(array $expectedData, ?string $keyToGetOnValue): void
    {
        $parameters = $keyToGetOnValue ? sprintf('(keyToGetOnValue: "%s") ', $keyToGetOnValue) : '';
        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                      localizedCatalogGroupOptions $parameters{
                        id
                        value
                        label
                        options
                      }
                    }
                GQL,
                null,
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use ($expectedData) {
                    $responseData = $response->toArray();
                    $this->assertSame($expectedData, $responseData['data']['localizedCatalogGroupOptions']);
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
                        'id' => 'b2c_test',
                        'value' => 'b2c_test',
                        'label' => 'B2C Test Catalog',
                        'options' => [
                            ['value' => $this->getUri('localized_catalogs', '1'), 'label' => 'B2C French Store View'],
                            ['value' => $this->getUri('localized_catalogs', '2'), 'label' => 'B2C English Store View'],
                        ],
                    ],
                    [
                        'id' => 'b2b_test',
                        'value' => 'b2b_test',
                        'label' => 'B2B Test Catalog',
                        'options' => [
                            ['value' => $this->getUri('localized_catalogs', '3'), 'label' => 'B2B English Store View'],
                        ],
                    ],
                ],
            ],
            // todo testes ajoutés mais pas eu le temps de vérifier, car problème de btree (jsonb)
            [
                [
                    [
                        'id' => 'b2c_test',
                        'value' => 'b2c_test',
                        'label' => 'B2C Test Catalog',
                        'options' => [
                            ['value' => 'b2c_fr'],
                            ['value' => 'b2c_en'],
                        ],
                    ],
                    [
                        'id' => 'b2b_test',
                        'value' => 'b2b_test',
                        'label' => 'B2B Test Catalog',
                        'options' => [
                            ['value' => 'b2b_en'],
                        ],
                    ],
                ],
                'code'
            ],
        ];
    }
}
