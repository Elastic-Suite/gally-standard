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

use Gally\Test\AbstractTestCase;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestGraphQlToTest;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ProductSourceFieldLabelTest extends AbstractTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::loadFixture([
            __DIR__ . '/../../fixtures/source_field_label.yaml',
            __DIR__ . '/../../fixtures/source_field.yaml',
            __DIR__ . '/../../fixtures/metadata.yaml',
            __DIR__ . '/../../fixtures/catalogs.yaml',
        ]);
    }

    /**
     * Success cases, shared with the REST test.
     *
     * Fixture facts these rely on (src/Product/Tests/fixtures/):
     *  - size and color have a label per catalog: Taille/Couleur on b2c_fr, Size/Color on b2c_en
     *  - stock has no label row but a defaultLabel column: 'Stock data'
     *  - brand has neither, so it falls back to ucfirst(code)
     *  - 'nope' does not exist, and falls back to ucfirst(code) exactly like brand
     */
    public static function getLabelsDataProvider(): iterable
    {
        yield 'localized labels in french' => [
            ['size', 'color'],
            'b2c_fr',
            [
                ['code' => 'size', 'label' => 'Taille'],
                ['code' => 'color', 'label' => 'Couleur'],
            ],
        ];

        yield 'localized labels in english' => [
            ['size', 'color'],
            'b2c_en',
            [
                ['code' => 'size', 'label' => 'Size'],
                ['code' => 'color', 'label' => 'Color'],
            ],
        ];

        yield 'no label row falls back to the default label column' => [
            ['stock'],
            'b2c_fr',
            [['code' => 'stock', 'label' => 'Stock data']],
        ];

        yield 'no label at all falls back to the code' => [
            ['brand'],
            'b2c_fr',
            [['code' => 'brand', 'label' => 'Brand']],
        ];

        yield 'unknown code is answered with the code' => [
            ['nope'],
            'b2c_fr',
            [['code' => 'nope', 'label' => 'Nope']],
        ];

        yield 'unknown code is indistinguishable from an unlabelled one, order is kept' => [
            ['size', 'nope', 'brand'],
            'b2c_fr',
            [
                ['code' => 'size', 'label' => 'Taille'],
                ['code' => 'nope', 'label' => 'Nope'],
                ['code' => 'brand', 'label' => 'Brand'],
            ],
        ];

        yield 'duplicate codes collapse to one entry' => [
            ['size', 'size'],
            'b2c_fr',
            [['code' => 'size', 'label' => 'Taille']],
        ];
    }

    /**
     * @dataProvider getLabelsDataProvider
     *
     * @param string[]                                       $codes
     * @param array<int, array{code: string, label: string}> $expectedData
     */
    public function testGetCollection(array $codes, string $localizedCatalog, array $expectedData): void
    {
        // No user is passed, so no bearer token is sent: the endpoint must answer anonymously.
        $this->validateApiCall(
            new RequestGraphQlToTest($this->buildQuery($codes, $localizedCatalog), null),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use ($expectedData) {
                    $responseData = $response->toArray();
                    $this->assertSame($expectedData, $responseData['data']['productSourceFieldLabels']);
                }
            )
        );
    }

    public function testMissingCodes(): void
    {
        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                      productSourceFieldLabels (codes: [], localizedCatalog: "b2c_fr") {
                        code
                        label
                      }
                    }
                GQL,
                null
            ),
            new ExpectedResponse(
                200,
                function () {
                    $this->assertGraphQlError('The codes argument is required and cannot be empty.');
                }
            )
        );
    }

    public function testUnknownLocalizedCatalog(): void
    {
        $this->validateApiCall(
            new RequestGraphQlToTest($this->buildQuery(['size'], 'not_a_catalog'), null),
            new ExpectedResponse(
                200,
                function () {
                    $this->assertGraphQlError('Missing localized catalog [not_a_catalog]');
                }
            )
        );
    }

    /**
     * There is no operation returning every source field: the code list is not discoverable.
     */
    public function testNoCollectionWithoutCodes(): void
    {
        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                      productSourceFieldLabels (localizedCatalog: "b2c_fr") {
                        code
                        label
                      }
                    }
                GQL,
                null
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) {
                    $responseData = $response->toArray(false);
                    $this->assertArrayHasKey('errors', $responseData);
                    $this->assertArrayNotHasKey('productSourceFieldLabels', $responseData['data'] ?? []);
                }
            )
        );
    }

    /**
     * @param string[] $codes
     */
    private function buildQuery(array $codes, string $localizedCatalog): string
    {
        $codesArg = json_encode($codes, \JSON_THROW_ON_ERROR);

        return <<<GQL
            {
              productSourceFieldLabels (codes: {$codesArg}, localizedCatalog: "{$localizedCatalog}") {
                code
                label
              }
            }
        GQL;
    }
}
