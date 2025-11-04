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

namespace Gally\Metadata\Tests\Api\Rest;

use Gally\Index\Entity\Index\Mapping\FieldInterface;
use Gally\Metadata\Entity\SourceField;
use Gally\Metadata\Repository\SourceFieldLabelRepository;
use Gally\Metadata\Repository\SourceFieldRepository;
use Gally\Test\AbstractEntityTestWithUpdate;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestToTest;
use Gally\User\Constant\Role;
use Gally\User\Entity\User;
use Symfony\Contracts\HttpClient\ResponseInterface;

class SourceFieldTest extends AbstractEntityTestWithUpdate
{
    protected static function getFixtureFiles(): array
    {
        return [
            __DIR__ . '/../../fixtures/catalogs.yaml',
            __DIR__ . '/../../fixtures/source_field.yaml',
            __DIR__ . '/../../fixtures/source_field_label.yaml',
            __DIR__ . '/../../fixtures/source_field_option.yaml',
            __DIR__ . '/../../fixtures/metadata.yaml',
        ];
    }

    protected function getEntityClass(): string
    {
        return SourceField::class;
    }

    public function createDataProvider(): iterable
    {
        $adminUser = $this->getUser(Role::ROLE_ADMIN);

        return [
            [null, ['code' => 'description', 'metadata' => $this->getUri('metadata', '3')], 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), ['code' => 'description', 'metadata' => $this->getUri('metadata', '3')], 403],
            [$adminUser, ['code' => 'description', 'metadata' => $this->getUri('metadata', '3')], 201],
            [$adminUser, ['code' => 'weight', 'metadata' => $this->getUri('metadata', '3')], 201],
            [$adminUser, ['code' => 'image', 'metadata' => $this->getUri('metadata', '4')], 201],
            [$adminUser, ['code' => 'length', 'isSearchable' => true, 'metadata' => $this->getUri('metadata', '3'), 'weight' => 2], 201],
            [$adminUser, ['code' => 'height', 'isUsedInAutocomplete' => true, 'metadata' => $this->getUri('metadata', '3')], 201],
            [$adminUser, ['code' => 'width', 'isFilterable' => false, 'isUsedInAutocomplete' => true, 'metadata' => $this->getUri('metadata', '3')], 201],
            [$adminUser, ['code' => 'description'], 422, 'metadata: This value should not be blank.'],
            [$adminUser, ['metadata' => $this->getUri('metadata', '3')], 422, 'code: This value should not be blank.'],
            [
                $adminUser,
                ['code' => 'long_description', 'metadata' => $this->getUri('metadata', '3'), 'type' => 'description'],
                422,
                'type: The value you selected is not a valid choice.',
            ],
            [
                $adminUser,
                ['code' => 'description', 'metadata' => $this->getUri('metadata', 'notExist')],
                400,
                'Item not found for "' . $this->getUri('metadata', 'notExist') . '".',
            ],
            [
                $adminUser,
                ['code' => 'name', 'metadata' => $this->getUri('metadata', '3')],
                422,
                'code: A field with this code already exist for this entity.',
            ],
            [
                $adminUser,
                ['code' => 'color', 'isSearchable' => true, 'metadata' => $this->getUri('metadata', '3'), 'weight' => 0],
                422,
                'weight: The value you selected is not a valid choice.',
            ],
            [
                $adminUser,
                ['code' => 'color', 'isSearchable' => true, 'metadata' => $this->getUri('metadata', '3'), 'weight' => 11],
                422,
                'weight: The value you selected is not a valid choice.',
            ],
            [$adminUser, ['code' => 'my_category', 'metadata' => $this->getUri('metadata', '3'), 'type' => 'category'], 201],
            [
                $adminUser,
                ['code' => 'my_category.id', 'metadata' => $this->getUri('metadata', '3'), 'type' => 'keyword'],
                500,
                "You can't create a source field with the code 'my_category.id' because a source field with the code 'my_category' exists.",
            ],
            [$adminUser, ['code' => 'my_price.price', 'metadata' => $this->getUri('metadata', '3'), 'type' => 'float'], 201],
            [
                $adminUser,
                ['code' => 'my_price', 'metadata' => $this->getUri('metadata', '3'), 'type' => 'price'],
                500,
                "You can't create a source field with the code 'my_price' because a source field with the code 'my_price.*' exists.",
            ],
            // Create label while creating sourceField
            [
                $adminUser,
                [
                    'code' => 'is_new',
                    'metadata' => $this->getUri('metadata', '3'),
                    'defaultLabel' => 'New',
                    'labels' => [
                        [
                            'localizedCatalog' => $this->getUri('localized_catalogs', '1'),
                            'label' => 'Nouveautés',
                        ],
                    ],
                ],
                201,
            ],
            [
                $adminUser,
                [
                    'code' => 'tags',
                    'metadata' => $this->getUri('metadata', '3'),
                    'defaultLabel' => 'Tags',
                    'labels' => [
                        [
                            'localizedCatalog' => $this->getUri('localized_catalogs', '1'),
                            'label' => 'Mots clés',
                        ],
                        [
                            'localizedCatalog' => $this->getUri('localized_catalogs', '2'),
                            'label' => 'Tags',
                        ],
                    ],
                ],
                201,
            ],
            [$adminUser, ['code' => 'reference_reference_source_field', 'metadata' => $this->getUri('metadata', '3'), 'type' => SourceField\Type::TYPE_REFERENCE], 201],
            [$adminUser, ['code' => 'reference_default_source_field', 'metadata' => $this->getUri('metadata', '3'), 'type' => SourceField\Type::TYPE_REFERENCE], 201],
            [$adminUser, ['code' => 'reference_edge_ngram_source_field', 'metadata' => $this->getUri('metadata', '3'), 'type' => SourceField\Type::TYPE_REFERENCE, 'defaultSearchAnalyzer' => FieldInterface::ANALYZER_EDGE_NGRAM], 201],
        ];
    }

    protected function getJsonCreationValidation(array $expectedData): array
    {
        // Test that autocomplete sourceField are always filterable
        if (\array_key_exists('isUsedInAutocomplete', $expectedData) && $expectedData['isUsedInAutocomplete']) {
            $expectedData['isFilterable'] = true;
        }

        if (\array_key_exists('isFilterable', $expectedData) && !$expectedData['isFilterable']) {
            $expectedData['isUsedInAutocomplete'] = false;
        }

        unset($expectedData['labels']);

        return parent::getJsonCreationValidation($expectedData);
    }

    public function getDataProvider(): iterable
    {
        $user = $this->getUser(Role::ROLE_CONTRIBUTOR);

        return [
            [null, 14, ['id' => 14, 'code' => 'name', 'weight' => 10], 401],
            [$user, 14, ['id' => 14, 'code' => 'name', 'weight' => 10], 200],
            [$this->getUser(Role::ROLE_ADMIN), 14, ['id' => 14, 'code' => 'name', 'weight' => 10], 200],
            [$user, 14, ['id' => 14, 'code' => 'name', 'weight' => 10], 200],
            [$user, 16, ['id' => 16, 'code' => 'description', 'weight' => 1], 200],
            [$user, 29, ['id' => 29, 'code' => 'length', 'weight' => 2], 200],
            // Check if default search analyzer is set to 'reference' when the source fields type is 'reference'.
            [$user, 36, ['id' => 36, 'code' => 'reference_reference_source_field', 'defaultSearchAnalyzer' => FieldInterface::ANALYZER_REFERENCE], 200],
            [$user, 37, ['id' => 37, 'code' => 'reference_default_source_field', 'defaultSearchAnalyzer' => FieldInterface::ANALYZER_REFERENCE], 200],
            // Check if we can set custom default search analyzer (diffrent to'reference)' when the source fields type is 'reference'.
            [$user, 38, ['id' => 38, 'code' => 'reference_edge_ngram_source_field', 'defaultSearchAnalyzer' => FieldInterface::ANALYZER_EDGE_NGRAM], 200],
            [$user, 100, [], 404],
        ];
    }

    public function deleteDataProvider(): iterable
    {
        $adminUser = $this->getUser(Role::ROLE_ADMIN);

        return [
            [null, 14, 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 14, 403],
            [$adminUser, 14, 204],
            [$adminUser, 17, 400], // Can't remove system source field
            [$adminUser, 22, 204],
            [$adminUser, 99, 404],
        ];
    }

    public function getCollectionDataProvider(): iterable
    {
        return [
            [null, 28, 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 28, 200],
            [$this->getUser(Role::ROLE_ADMIN), 28, 200],
        ];
    }

    public function patchUpdateDataProvider(): iterable
    {
        return [
            [null, 18, ['weight' => 10, 'isSpellchecked' => true], 405],
        ];
    }

    /**
     * @dataProvider putUpdateDataProvider
     *
     * @depends testPatchUpdate
     */
    public function testPutUpdate(
        ?User $user,
        int|string $id,
        array $data,
        int $responseCode,
        ?string $message = null,
        ?string $validRegex = null
    ): ResponseInterface {
        $response = parent::testPutUpdate($user, $id, $data, $responseCode, $message, $validRegex);

        /** @var SourceFieldRepository $sourceFieldRepository */
        $sourceFieldRepository = static::getContainer()->get(SourceFieldLabelRepository::class);
        $labels = $sourceFieldRepository->findBy(['sourceField' => $id]);
        $this->assertCount(\count($data['labels'] ?? []), $labels);

        return $response;
    }

    public function putUpdateDataProvider(): iterable
    {
        $adminUser = $this->getUser(Role::ROLE_ADMIN);

        return [
            [null, 18, ['weight' => 10, 'isSpellchecked' => true], 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 18, ['weight' => 10, 'isSpellchecked' => true], 200],
            [$adminUser, 18, ['weight' => 10, 'isSpellchecked' => true], 200],
            [
                $adminUser,
                18,
                ['isFilterable' => true],
                400,
                "The source field 'sku' cannot be updated because it is a system source field, only the value of 'defaultLabel', 'weight', 'isSpellchecked', 'defaultSearchAnalyzer', 'isSpannable' can be changed.",
            ],
            [
                $adminUser,
                18,
                ['isSystem' => false],
                400,
                "The source field 'sku' cannot be updated because it is a system source field, only the value of 'defaultLabel', 'weight', 'isSpellchecked', 'defaultSearchAnalyzer', 'isSpannable' can be changed.",
            ],
            [
                $adminUser,
                18,
                ['weight' => 5],
                200,
            ],
            [ // Create labels for sourceField
                $adminUser,
                35,
                [
                    'labels' => [
                        [
                            'localizedCatalog' => $this->getUri('localized_catalogs', '1'),
                            'label' => 'Pastilles',
                        ],
                        [
                            'localizedCatalog' => $this->getUri('localized_catalogs', '2'),
                            'label' => 'Flags',
                        ],
                    ],
                ],
                200,
            ],
            [ // Update label for sourceField
                $adminUser,
                34,
                [
                    'labels' => [
                        [
                            'localizedCatalog' => $this->getUri('localized_catalogs', '1'),
                            'label' => 'Les nouveautés updated',
                        ],
                    ],
                ],
                200,
            ],
            [ // Replace labels for sourceField
                $adminUser,
                34,
                [
                    'labels' => [
                        [
                            'localizedCatalog' => $this->getUri('localized_catalogs', '2'),
                            'label' => 'New products',
                        ],
                    ],
                ],
                200,
            ],
            [ // Test that autocomplete sourceField are always filterable
                $adminUser,
                25,
                ['isUsedInAutocomplete' => true],
                200,
            ],
            [ // Test that if we remove the filterable property of a field used in autocomplete, that remove the usedInAutocomplete property too
                $adminUser,
                25,
                ['isFilterable' => false],
                200,
            ],
            [ // Test that we can update defaultSearchAnalyzer when type is reference.
                $adminUser,
                24,
                ['defaultSearchAnalyzer' => FieldInterface::ANALYZER_STANDARD],
                200,
            ],
        ];
    }

    protected function getJsonUpdateValidation(array $expectedData): array
    {
        unset($expectedData['labels']);

        // Test that autocomplete sourceField are always filterable
        if (\array_key_exists('isUsedInAutocomplete', $expectedData) && $expectedData['isUsedInAutocomplete']) {
            $expectedData['isFilterable'] = true;
        }

        if (\array_key_exists('isFilterable', $expectedData) && !$expectedData['isFilterable']) {
            $expectedData['isUsedInAutocomplete'] = false;
        }

        return parent::getJsonCreationValidation($expectedData);
    }

    /**
     * @dataProvider searchColumnsFilterDataProvider
     */
    public function testSearchColumnsFilter(string $search, int $expectedItemNumber): void
    {
        $request = new RequestToTest(
            'GET', $this->getApiPath() . '?defaultLabel=' . $search,
            $this->getUser(Role::ROLE_CONTRIBUTOR)
        );
        $expectedResponse = new ExpectedResponse(
            200,
            function (ResponseInterface $response) use ($expectedItemNumber) {
                $shortName = $this->getShortName();
                $this->assertJsonContains(
                    [
                        '@context' => $this->getRoute("contexts/$shortName"),
                        '@id' => $this->getRoute($this->getApiPath()),
                        '@type' => 'hydra:Collection',
                        'hydra:totalItems' => $expectedItemNumber,
                    ],
                );
            }
        );

        $this->validateApiCall($request, $expectedResponse);
    }

    protected function searchColumnsFilterDataProvider(): iterable
    {
        yield ['sku', 1];
        yield ['Name', 2];
        yield ['Nom', 1];
    }

    /**
     * @depends testPutUpdate
     *
     * @dataProvider bulkDataProvider
     */
    public function testBulk(
        ?User $user,
        array $sourceFields,
        int $expectedSourceFieldNumber,
        array $expectedResponseData,
        array $expectedSearchValues,
        int $responseCode,
        ?string $message = null
    ): void {
        $request = new RequestToTest('POST', "{$this->getApiPath()}/bulk", $user, $sourceFields);
        $expectedResponse = new ExpectedResponse(
            $responseCode,
            function (ResponseInterface $response) use ($sourceFields, $expectedSourceFieldNumber, $expectedResponseData, $expectedSearchValues) {
                $this->assertJsonContains(['hydra:member' => $expectedResponseData]);
                $sourceFieldRepository = static::getContainer()->get(SourceFieldRepository::class);
                $existingSourceFields = $sourceFieldRepository->findAll();
                $this->assertCount($expectedSourceFieldNumber, $existingSourceFields);
                $this->assertCount(
                    7 /* base properties */ + \count($sourceFieldRepository->getManagedSourceFieldProperty()),
                    (array) reset($existingSourceFields),
                    'Please check, if you just add a new sourceField property, to add it in the bulk query, see \Gally\Metadata\State\SourceFieldProcessor and \Gally\Metadata\Repository\SourceFieldRepository.'
                );
                foreach ($sourceFields as $sourceFieldData) {
                    $sourceField = $sourceFieldRepository->findOneBy(
                        [
                            'metadata' => (int) str_replace($this->getRoute('metadata') . '/', '', $sourceFieldData['metadata']),
                            'code' => $sourceFieldData['code'],
                        ]
                    );
                    $this->assertCount(\count($sourceFieldData['labels'] ?? []), $sourceField->getLabels());
                    $this->assertSame($expectedSearchValues[$sourceFieldData['code']], $sourceField->getSearch());
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
        yield [null, [], 20, [], [], 401];
        yield [$this->getUser(Role::ROLE_CONTRIBUTOR), [], 20, [], [], 403];

        // Incomplete / invalid data
        yield [
            $adminUser, // Api User
            [ // Source field post data
                ['weight' => 1],
                ['code' => 'new_source_field_1', 'weight' => 1],
                ['code' => 'sku', 'metadata' => $this->getUri('metadata', '3'), 'isFilterable' => true],
            ],
            20, // Expected source field number
            [], // Expected data in response
            [], // Expected search values
            400, // Expected response code
            // Expected error messages
            'Option #0: A code value is required for source field. ' .
            'Option #1: A metadata value is required for source field. ' .
            "Option #2: The source field 'sku' cannot be updated because it is a system source field, only the value of 'defaultLabel', 'weight', 'isSpellchecked', 'defaultSearchAnalyzer', 'isSpannable' can be changed.",
        ];

        // With one sourceField
        yield [
            $adminUser, // Api User
            [ // Source field post data
                ['code' => 'new_source_field_1', 'metadata' => $this->getUri('metadata', '3'), 'weight' => 1],
            ],
            26, // Expected source field number
            [], // Expected data in response
            [ // Expected search values
                'new_source_field_1' => 'new_source_field_1 New_source_field_1',
            ],
            200, // Expected response code
        ];
        yield [
            $adminUser, // Api User
            [ // Source field post data
                ['code' => 'new_source_field_2', 'metadata' => $this->getUri('metadata', '3'), 'weight' => 1],
                ['code' => 'new_source_field_3', 'weight' => 1],
                ['code' => 'new_source_field_3', 'metadata' => $this->getUri('metadata', '3'), 'weight' => 1],
                ['code' => 'sku', 'metadata' => $this->getUri('metadata', '3'), 'weight' => 2, 'isSpellchecked' => true],
            ],
            28, // Expected source field number
            [], // Expected data in response
            [ // Expected search values
                'new_source_field_2' => 'new_source_field_2 New_source_field_2',
                'new_source_field_3' => 'new_source_field_3 New_source_field_3',
                'sku' => 'sku',
            ],
            400, // Expected response code
            'Option #1: A metadata value is required for source field.',
        ];
        yield [
            $adminUser, // Api User
            [ // Source field post data
                ['code' => 'new_source_field_2', 'metadata' => $this->getUri('metadata', '3'), 'defaultLabel' => 'New source field 2', 'isFilterable' => true],
                ['code' => 'new_source_field_4', 'metadata' => $this->getUri('metadata', '3'), 'weight' => 5],
            ],
            29, // Expected source field number
            [ // Expected data in response
                0 => ['isFilterable' => true, 'weight' => 1, 'isSystem' => false],
                1 => ['isFilterable' => null, 'weight' => 5, 'isSystem' => false],
            ],
            [ // Expected search values
                'new_source_field_2' => 'new_source_field_2 New source field 2',
                'new_source_field_4' => 'new_source_field_4 New_source_field_4',
            ],
            200, // Expected response code
        ];
        yield [
            $adminUser, // Api User
            [ // Source field post data
                [
                    'code' => 'sku',
                    'metadata' => $this->getUri('metadata', '3'),
                    'labels' => [
                        ['localizedCatalog' => $this->getUri('localized_catalogs', '2'), 'label' => 'Reference'],
                    ],
                ],
                [
                    'code' => 'new_source_field_2',
                    'metadata' => $this->getUri('metadata', '3'),
                    'weight' => 1,
                    'defaultLabel' => 'New source field 2',
                    'labels' => [
                        ['localizedCatalog' => $this->getUri('localized_catalogs', '1'), 'label' => 'Localized label source field 2'],
                    ],
                ],
                [
                    'code' => 'new_source_field_5',
                    'metadata' => $this->getUri('metadata', '3'),
                    'weight' => 1,
                    'labels' => [
                        ['localizedCatalog' => $this->getUri('localized_catalogs', '1'), 'label' => 'Localized label source field 5'],
                        ['localizedCatalog' => $this->getUri('localized_catalogs', '2'), 'label' => 'Localized label 2 source field 5'],
                    ],
                ],
            ],
            30, // Expected source field number
            [ // Expected data in response
                0 => ['code' => 'sku'],
                1 => ['code' => 'new_source_field_2'],
                2 => ['code' => 'new_source_field_5'],
            ],
            [ // Expected search values
                'sku' => 'sku Reference',
                'new_source_field_2' => 'new_source_field_2 New source field 2',
                'new_source_field_5' => 'new_source_field_5 Localized label 2 source field 5',
            ],
            200, // Expected response code
        ];
        yield [
            $adminUser, // Api User
            [ // Source field post data
                [
                    'code' => 'new_source_field_2',
                    'metadata' => $this->getUri('metadata', '3'),
                    'weight' => 1,
                    'defaultLabel' => 'New source field 2',
                    'labels' => [
                        ['localizedCatalog' => $this->getUri('localized_catalogs', '2'), 'label' => 'Localized label 2 source field 2'],
                    ],
                ],
                [
                    'code' => 'new_source_field_5',
                    'metadata' => $this->getUri('metadata', '3'),
                    'weight' => 1,
                    'labels' => [
                        ['localizedCatalog' => $this->getUri('localized_catalogs', '2'), 'label' => 'Localized label 2 source field 5'],
                    ],
                ],
            ],
            30, // Expected source field number
            [ // Expected data in response
                0 => ['code' => 'new_source_field_2'],
                1 => ['code' => 'new_source_field_5'],
            ],
            [ // Expected search values
                'new_source_field_2' => 'new_source_field_2 Localized label 2 source field 2',
                'new_source_field_5' => 'new_source_field_5 Localized label 2 source field 5',
            ],
            200, // Expected response code
        ];
    }
}
