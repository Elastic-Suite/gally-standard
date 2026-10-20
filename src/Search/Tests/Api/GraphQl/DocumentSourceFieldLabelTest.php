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

namespace Gally\Search\Tests\Api\GraphQl;

use Gally\Test\AbstractTestCase;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestGraphQlToTest;
use Symfony\Contracts\HttpClient\ResponseInterface;

class DocumentSourceFieldLabelTest extends AbstractTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::loadFixture([
            __DIR__ . '/../../fixtures/source_field_label_document.yaml',
            __DIR__ . '/../../fixtures/source_field_label.yaml',
            __DIR__ . '/../../fixtures/source_field.yaml',
            __DIR__ . '/../../fixtures/metadata.yaml',
            __DIR__ . '/../../fixtures/catalogs.yaml',
        ]);
    }

    /**
     * Success cases, shared with the REST test.
     *
     * Fixture facts these rely on (src/Search/Tests/fixtures/):
     *  - the product metadata is registered under the entity type product_document
     *  - size and color have a label per catalog: Taille/Couleur on b2b_fr, Size/Color on b2c_en
     *  - name exists on product_document and on category, and only the category one has a label
     *  - 'nope' does not exist, and falls back to ucfirst(code)
     */
    public static function getLabelsDataProvider(): iterable
    {
        yield 'localized labels in french' => [
            'product_document',
            ['size', 'color'],
            'b2b_fr',
            [
                ['code' => 'size', 'label' => 'Taille'],
                ['code' => 'color', 'label' => 'Couleur'],
            ],
        ];

        yield 'localized labels in english' => [
            'product_document',
            ['size', 'color'],
            'b2c_en',
            [
                ['code' => 'size', 'label' => 'Size'],
                ['code' => 'color', 'label' => 'Color'],
            ],
        ];

        yield 'label of another entity type' => [
            'category',
            ['name'],
            'b2b_fr',
            [['code' => 'name', 'label' => 'Nom de la catégorie']],
        ];

        yield 'same code on another entity type does not get its label' => [
            'product_document',
            ['name'],
            'b2b_fr',
            [['code' => 'name', 'label' => 'Name']],
        ];

        yield 'unknown code is answered with the code, order is kept' => [
            'product_document',
            ['size', 'nope'],
            'b2b_fr',
            [
                ['code' => 'size', 'label' => 'Taille'],
                ['code' => 'nope', 'label' => 'Nope'],
            ],
        ];

        yield 'the fallback only capitalises the first letter' => [
            'product_document',
            ['not_a_field'],
            'b2b_fr',
            [['code' => 'not_a_field', 'label' => 'Not_a_field']],
        ];
    }

    /**
     * @dataProvider getLabelsDataProvider
     *
     * @param string[]                                       $codes
     * @param array<int, array{code: string, label: string}> $expectedData
     */
    public function testGetCollection(string $entityType, array $codes, string $localizedCatalog, array $expectedData): void
    {
        // No user is passed, so no bearer token is sent: the endpoint must answer anonymously.
        $this->validateApiCall(
            new RequestGraphQlToTest($this->buildQuery($entityType, $codes, $localizedCatalog), null),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use ($expectedData) {
                    $responseData = $response->toArray();
                    $this->assertSame($expectedData, $responseData['data']['documentSourceFieldLabels']);
                }
            )
        );
    }

    public function testMissingCodes(): void
    {
        $this->validateApiCall(
            new RequestGraphQlToTest($this->buildQuery('product_document', [], 'b2b_fr'), null),
            new ExpectedResponse(
                200,
                function () {
                    $this->assertGraphQlError('The codes argument is required and cannot be empty.');
                }
            )
        );
    }

    public function testEmptyEntityType(): void
    {
        $this->validateApiCall(
            new RequestGraphQlToTest($this->buildQuery('', ['size'], 'b2b_fr'), null),
            new ExpectedResponse(
                200,
                function () {
                    $this->assertGraphQlError('The entityType argument is required.');
                }
            )
        );
    }

    public function testUnknownEntityType(): void
    {
        $this->validateApiCall(
            new RequestGraphQlToTest($this->buildQuery('not_an_entity', ['size'], 'b2b_fr'), null),
            new ExpectedResponse(
                200,
                function () {
                    $this->assertGraphQlError('Entity type [not_an_entity] does not exist');
                }
            )
        );
    }

    public function testUnknownLocalizedCatalog(): void
    {
        $this->validateApiCall(
            new RequestGraphQlToTest($this->buildQuery('product_document', ['size'], 'not_a_catalog'), null),
            new ExpectedResponse(
                200,
                function () {
                    $this->assertGraphQlError('Missing localized catalog [not_a_catalog]');
                }
            )
        );
    }

    /**
     * @param string[] $codes
     */
    private function buildQuery(string $entityType, array $codes, string $localizedCatalog): string
    {
        $codesArg = json_encode($codes, \JSON_THROW_ON_ERROR);

        return <<<GQL
            {
              documentSourceFieldLabels (entityType: "{$entityType}", codes: {$codesArg}, localizedCatalog: "{$localizedCatalog}") {
                code
                label
              }
            }
        GQL;
    }
}
