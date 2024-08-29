<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @package   Gally
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Gally\Metadata\Tests\Api\Rest;

use Gally\Metadata\Model\SourceField;
use Gally\Metadata\Repository\SourceFieldLabelRepository;
use Gally\Metadata\Repository\SourceFieldRepository;
use Gally\Test\AbstractEntityTestWithUpdate;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestToTest;
use Gally\User\Constant\Role;
use Gally\User\Model\User;
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
            [null, ['code' => 'description', 'metadata' => '/metadata/1'], 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), ['code' => 'description', 'metadata' => '/metadata/1'], 403],
            [$adminUser, ['code' => 'description', 'metadata' => '/metadata/1'], 201],
            [$adminUser, ['code' => 'weight', 'metadata' => '/metadata/1'], 201],
            [$adminUser, ['code' => 'image', 'metadata' => '/metadata/2'], 201],
            [$adminUser, ['code' => 'length', 'isSearchable' => true, 'metadata' => '/metadata/1', 'weight' => 2], 201],
            [$adminUser, ['code' => 'height', 'isUsedInAutocomplete' => true, 'metadata' => '/metadata/1'], 201],
            [$adminUser, ['code' => 'width', 'isFilterable' => false, 'isUsedInAutocomplete' => true, 'metadata' => '/metadata/1'], 201],
            [$adminUser, ['code' => 'description'], 422, 'metadata: This value should not be blank.'],
            [$adminUser, ['metadata' => '/metadata/1'], 422, 'code: This value should not be blank.'],
            [
                $adminUser,
                ['code' => 'long_description', 'metadata' => '/metadata/1', 'type' => 'description'],
                422,
                'type: The value you selected is not a valid choice.',
            ],
            [
                $adminUser,
                ['code' => 'description', 'metadata' => '/metadata/notExist'],
                400,
                'Item not found for "/metadata/notExist".',
            ],
            [
                $adminUser,
                ['code' => 'name', 'metadata' => '/metadata/1'],
                422,
                'code: A field with this code already exist for this entity.',
            ],
            [
                $adminUser,
                ['code' => 'color', 'isSearchable' => true, 'metadata' => '/metadata/1', 'weight' => 0],
                422,
                'weight: The value you selected is not a valid choice.',
            ],
            [
                $adminUser,
                ['code' => 'color', 'isSearchable' => true, 'metadata' => '/metadata/1', 'weight' => 11],
                422,
                'weight: The value you selected is not a valid choice.',
            ],
            [$adminUser, ['code' => 'my_category', 'metadata' => '/metadata/1', 'type' => 'category'], 201],
            [
                $adminUser,
                ['code' => 'my_category.id', 'metadata' => '/metadata/1', 'type' => 'keyword'],
                500,
                "You can't create a source field with the code 'my_category.id' because a source field with the code 'my_category' exists.",
            ],
            [$adminUser, ['code' => 'my_price.price', 'metadata' => '/metadata/1', 'type' => 'float'], 201],
            [
                $adminUser,
                ['code' => 'my_price', 'metadata' => '/metadata/1', 'type' => 'price'],
                500,
                "You can't create a source field with the code 'my_price' because a source field with the code 'my_price.*' exists.",
            ],
            // Create label while creating sourceField
            [
                $adminUser,
                [
                    'code' => 'is_new',
                    'metadata' => '/metadata/1',
                    'defaultLabel' => 'New',
                    'labels' => [
                        [
                            'localizedCatalog' => '/localized_catalogs/1',
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
                    'metadata' => '/metadata/1',
                    'defaultLabel' => 'Tags',
                    'labels' => [
                        [
                            'localizedCatalog' => '/localized_catalogs/1',
                            'label' => 'Mots clés',
                        ],
                        [
                            'localizedCatalog' => '/localized_catalogs/2',
                            'label' => 'Tags',
                        ],
                    ],
                ],
                201,
            ],
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

        return parent::getJsonCreationValidation($expectedData);
    }

    public function getDataProvider(): iterable
    {
        $user = $this->getUser(Role::ROLE_CONTRIBUTOR);

        return [
            [null, 1, ['id' => 1, 'code' => 'name', 'weight' => 10], 401],
            [$user, 1, ['id' => 1, 'code' => 'name', 'weight' => 10], 200],
            [$this->getUser(Role::ROLE_ADMIN), 1, ['id' => 1, 'code' => 'name', 'weight' => 10], 200],
            [$user, 1, ['id' => 1, 'code' => 'name', 'weight' => 10], 200],
            [$user, 13, ['id' => 13, 'code' => 'description', 'weight' => 1], 200],
            [$user, 16, ['id' => 16, 'code' => 'length', 'weight' => 2], 200],
            [$user, 23, [], 404],
        ];
    }

    public function deleteDataProvider(): iterable
    {
        $adminUser = $this->getUser(Role::ROLE_ADMIN);

        return [
            [null, 1, 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 1, 403],
            [$adminUser, 1, 204],
            [$adminUser, 5, 400], // Can't remove system source field
            [$adminUser, 10, 204],
            [$adminUser, 99, 404],
        ];
    }

    public function getCollectionDataProvider(): iterable
    {
        return [
            [null, 20, 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 20, 200],
            [$this->getUser(Role::ROLE_ADMIN), 20, 200],
        ];

        //        todo upgrade: uncomment
        //        return [
        //            [null, 25, 401],
        //            [$this->getUser(Role::ROLE_CONTRIBUTOR), 25, 200],
        //            [$this->getUser(Role::ROLE_ADMIN), 25, 200],
        //        ];
    }

    public function patchUpdateDataProvider(): iterable
    {
        return [
            [null, 5, ['weight' => 10, 'isSpellchecked' => true], 405],
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
            [null, 5, ['weight' => 10, 'isSpellchecked' => true], 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 5, ['weight' => 10, 'isSpellchecked' => true], 403],
            [$adminUser, 5, ['weight' => 10, 'isSpellchecked' => true], 200],
            [
                $adminUser,
                5,
                ['isFilterable' => true],
                400,
                "The source field 'sku' cannot be updated because it is a system source field, only the value of 'weight' and 'isSpellchecked' can be changed.",
            ],
            [
                $adminUser,
                5,
                ['isSystem' => false],
                400,
                "The source field 'sku' cannot be updated because it is a system source field, only the value of 'weight' and 'isSpellchecked' can be changed.",
            ],
            [
                $adminUser,
                5,
                ['weight' => 5],
                200,
            ],
            [ // Create labels for sourceField
                $adminUser,
                11,
                [
                    'labels' => [
                        [
                            'localizedCatalog' => '/localized_catalogs/1',
                            'label' => 'Pastilles',
                        ],
                        [
                            'localizedCatalog' => '/localized_catalogs/2',
                            'label' => 'Flags',
                        ],
                    ],
                ],
                200,
            ],
            [ // Update label for sourceField
                $adminUser,
                19,
                [
                    'labels' => [
                        [
                            'localizedCatalog' => '/localized_catalogs/1',
                            'label' => 'Les nouveautés updated',
                        ],
                    ],
                ],
                200,
            ],
            [ // Replace labels for sourceField
                $adminUser,
                19,
                [
                    'labels' => [
                        [
                            'localizedCatalog' => '/localized_catalogs/2',
                            'label' => 'New products',
                        ],
                    ],
                ],
                200,
            ],
            [ // Test that autocomplete sourceField are always filterable
                $adminUser,
                17,
                ['isUsedInAutocomplete' => true],
                200,
            ],
            [ // Test that if we remove the filterable property of a field used in autocomplete, that remove the usedInAutocomplete property too
                $adminUser,
                17,
                ['isFilterable' => false],
                200,
            ],
        ];
    }

    protected function getJsonUpdateValidation(array $expectedData): array
    {
        foreach ($expectedData['labels'] ?? [] as $index => $label) {
            // Remove localized catalog in labels data because localized catalog are include as sub entity in response.
            // @see api/packages/gally-standard/src/Catalog/Model/LocalizedCatalog.php:55
            unset($expectedData['labels'][$index]['localizedCatalog']);
        }

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
                        '@context' => "/contexts/$shortName",
                        '@id' => $this->getApiPath(),
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
                $this->assertCount($expectedSourceFieldNumber, $sourceFieldRepository->findAll());
                $this->assertCount(
                    7 /* base properties */ + \count($sourceFieldRepository->getManagedSourceFieldProperty()),
                    (array) reset($existingSourceFields),
                    'Please check, if you just add a new sourceField property, to add it in the bulk query, see \Gally\Metadata\State\SourceFieldProcessor and \Gally\Metadata\Repository\SourceFieldRepository.'
                );
                foreach ($sourceFields as $sourceFieldData) {
                    $sourceField = $sourceFieldRepository->findOneBy(
                        [
                            'metadata' => (int) str_replace('/metadata/', '', $sourceFieldData['metadata']),
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

        return [
            [null, [], 20, [], [], 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), [], 20, [], [], 403],
        ];

        //        todo upgrade: uncomment
        //        // Test ACL
        //        yield [null, [], 20, [], [], 401];
        //        yield [$this->getUser(Role::ROLE_CONTRIBUTOR), [], 20, [], [], 403];
        //
        //        // Incomplete / invalid data
        //        yield [
        //            $adminUser, // Api User
        //            [ // Source field post data
        //                ['weight' => 1],
        //                ['code' => 'new_source_field_1', 'weight' => 1],
        //                ['code' => 'sku', 'metadata' => '/metadata/1', 'isFilterable' => true],
        //            ],
        //            20, // Expected source field number
        //            [], // Expected data in response
        //            [], // Expected search values
        //            400, // Expected response code
        //            //Expected error messages
        //            'Option #0: A code value is required for source field. ' .
        //            'Option #1: A metadata value is required for source field. ' .
        //            "Option #2: The source field 'sku' cannot be updated because it is a system source field, only the value of 'weight' and 'isSpellchecked' can be changed.",
        //        ];
        //
        //        // With one sourceField
        //        yield [
        //            $adminUser, // Api User
        //            [ // Source field post data
        //                ['code' => 'new_source_field_1', 'metadata' => '/metadata/1', 'weight' => 1],
        //            ],
        //            23, // Expected source field number
        //            [], // Expected data in response
        //            [ // Expected search values
        //                'new_source_field_1' => 'new_source_field_1 New_source_field_1',
        //            ],
        //            200, // Expected response code
        //        ];
        //        yield [
        //            $adminUser, // Api User
        //            [ // Source field post data
        //                ['code' => 'new_source_field_2', 'metadata' => '/metadata/1', 'weight' => 1],
        //                ['code' => 'new_source_field_3', 'weight' => 1],
        //                ['code' => 'new_source_field_3', 'metadata' => '/metadata/1', 'weight' => 1],
        //                ['code' => 'sku', 'metadata' => '/metadata/1', 'weight' => 2, 'isSpellchecked' => true],
        //            ],
        //            25, // Expected source field number
        //            [], // Expected data in response
        //            [ // Expected search values
        //                'new_source_field_2' => 'new_source_field_2 New_source_field_2',
        //                'new_source_field_3' => 'new_source_field_3 New_source_field_3',
        //                'sku' => 'sku',
        //            ],
        //            400, // Expected response code
        //            'Option #1: A metadata value is required for source field.',
        //        ];
        //        yield [
        //            $adminUser, // Api User
        //            [ // Source field post data
        //                ['code' => 'new_source_field_2', 'metadata' => '/metadata/1', 'defaultLabel' => 'New source field 2', 'isFilterable' => true],
        //                ['code' => 'new_source_field_4', 'metadata' => '/metadata/1', 'weight' => 5],
        //            ],
        //            26, // Expected source field number
        //            [ // Expected data in response
        //                0 => ['isFilterable' => true, 'weight' => 1, 'isSystem' => false],
        //                1 => ['isFilterable' => null, 'weight' => 5, 'isSystem' => false],
        //            ],
        //            [ // Expected search values
        //                'new_source_field_2' => 'new_source_field_2 New source field 2',
        //                'new_source_field_4' => 'new_source_field_4 New_source_field_4',
        //            ],
        //            200, // Expected response code
        //        ];
        //        yield [
        //            $adminUser, // Api User
        //            [ // Source field post data
        //                [
        //                    'code' => 'sku',
        //                    'metadata' => '/metadata/1',
        //                    'labels' => [
        //                        ['localizedCatalog' => '/localized_catalogs/2', 'label' => 'Reference'],
        //                    ],
        //                ],
        //                [
        //                    'code' => 'new_source_field_2',
        //                    'metadata' => '/metadata/1',
        //                    'weight' => 1,
        //                    'defaultLabel' => 'New source field 2',
        //                    'labels' => [
        //                        ['localizedCatalog' => '/localized_catalogs/1', 'label' => 'Localized label source field 2'],
        //                    ],
        //                ],
        //                [
        //                    'code' => 'new_source_field_5',
        //                    'metadata' => '/metadata/1',
        //                    'weight' => 1,
        //                    'labels' => [
        //                        ['localizedCatalog' => '/localized_catalogs/1', 'label' => 'Localized label source field 5'],
        //                        ['localizedCatalog' => '/localized_catalogs/2', 'label' => 'Localized label 2 source field 5'],
        //                    ],
        //                ],
        //            ],
        //            27, // Expected source field number
        //            [ // Expected data in response
        //                1 => ['labels' => [0 => ['label' => 'Localized label source field 2']]],
        //                2 => ['labels' => [1 => ['label' => 'Localized label 2 source field 5']]],
        //            ],
        //            [ // Expected search values
        //                'sku' => 'sku Reference',
        //                'new_source_field_2' => 'new_source_field_2 New source field 2',
        //                'new_source_field_5' => 'new_source_field_5 Localized label 2 source field 5',
        //            ],
        //            200, // Expected response code
        //        ];
        //        yield [
        //            $adminUser, // Api User
        //            [ // Source field post data
        //                [
        //                    'code' => 'new_source_field_2',
        //                    'metadata' => '/metadata/1',
        //                    'weight' => 1,
        //                    'defaultLabel' => 'New source field 2',
        //                    'labels' => [
        //                        ['localizedCatalog' => '/localized_catalogs/2', 'label' => 'Localized label 2 source field 2'],
        //                    ],
        //                ],
        //                [
        //                    'code' => 'new_source_field_5',
        //                    'metadata' => '/metadata/1',
        //                    'weight' => 1,
        //                    'labels' => [
        //                        ['localizedCatalog' => '/localized_catalogs/2', 'label' => 'Localized label 2 source field 5'],
        //                    ],
        //                ],
        //            ],
        //            27, // Expected source field number
        //            [ // Expected data in response
        //                0 => ['labels' => [0 => ['label' => 'Localized label 2 source field 2']]],
        //                1 => ['labels' => [0 => ['label' => 'Localized label 2 source field 5']]],
        //            ],
        //            [ // Expected search values
        //                'new_source_field_2' => 'new_source_field_2 Localized label 2 source field 2',
        //                'new_source_field_5' => 'new_source_field_5 Localized label 2 source field 5',
        //            ],
        //            200, // Expected response code
        //        ];
    }
}
