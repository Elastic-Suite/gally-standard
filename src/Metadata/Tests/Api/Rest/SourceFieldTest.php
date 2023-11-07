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
use Gally\Metadata\Model\SourceFieldOption;
use Gally\Metadata\Repository\SourceFieldOptionRepository;
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

    /**
     * {@inheritDoc}
     */
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

    /**
     * {@inheritDoc}
     */
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
            [$user, 21, [], 404],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function deleteDataProvider(): iterable
    {
        $adminUser = $this->getUser(Role::ROLE_ADMIN);

        return [
            [null, 1, 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 1, 403],
            [$adminUser, 1, 204],
            [$adminUser, 5, 400], // Can't remove system source field
            [$adminUser, 10, 204],
            [$adminUser, 21, 404],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getCollectionDataProvider(): iterable
    {
        return [
            [null, 18, 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 18, 200],
            [$this->getUser(Role::ROLE_ADMIN), 18, 200],
        ];
    }

    public function patchUpdateDataProvider(): iterable
    {
        return [
            [null, 5, ['weight' => 10, 'isSpellchecked' => true], 405],
        ];
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
                "The source field 'sku' cannot be updated because it is a system source field, only the value  of 'weight' and 'isSpellchecked' can be changed.",
            ],
            [
                $adminUser,
                5,
                ['isSystem' => false],
                400,
                "The source field 'sku' cannot be updated because it is a system source field, only the value  of 'weight' and 'isSpellchecked' can be changed.",
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
                18,
                [
                    'labels' => [
                        [
                            '@id' => '/source_field_labels/3',
                            'localizedCatalog' => '/localized_catalogs/1',
                            'label' => 'Les nouveautés updated',
                        ],
                    ],
                ],
                200,
            ],
            [ // Add new label for sourceField
                $adminUser,
                18,
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
        ];
    }

    protected function getJsonUpdateValidation(array $expectedData): array
    {
        foreach ($expectedData['labels'] ?? [] as $index => $label) {
            // Remove localized catalog in labels data because localized catalog are include as sub entity in response.
            // @see api/packages/gally-standard/src/Catalog/Model/LocalizedCatalog.php:55
            unset($expectedData['labels'][$index]['localizedCatalog']);

            // Remove id because the label will be removed and added on update
            unset($expectedData['labels'][$index]['@id']);
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

    private function searchColumnsFilterDataProvider(): iterable
    {
        yield ['sku', 1];
        yield ['Name', 2];
        yield ['Nom', 1];
    }

    /**
     * @depends testPutUpdate
     * @dataProvider addOptionsDataProvider
     */
    public function testAddOptions(
        ?User $user,
        int $sourceFieldId,
        array $options,
        int $expectedOptionCount,
        int $responseCode,
        ?string $message = null
    ): void {
        $request = new RequestToTest(
            'POST',
            "{$this->getApiPath()}/{$sourceFieldId}/add_options",
            $user,
            $options
        );
        $expectedResponse = new ExpectedResponse(
            $responseCode,
            function (ResponseInterface $response) use ($expectedOptionCount, $sourceFieldId, $options) {
                $sourceFieldOptionRepository = static::getContainer()->get(SourceFieldOptionRepository::class);
                $responseData = json_decode($response->getContent(), true);
                $this->assertCount($expectedOptionCount, $responseData['options']);

                foreach ($options as $optionData) {
                    /** @var SourceFieldOption $option */
                    $option = $sourceFieldOptionRepository->findOneBy(
                        ['code' => $optionData['code'], 'sourceField' => $sourceFieldId]
                    );
                    $this->assertCount(\count($optionData['labels'] ?? []), $option->getLabels());
                }
            },
            $message
        );

        $this->validateApiCall($request, $expectedResponse);
    }

    private function addOptionsDataProvider(): iterable
    {
        $adminUser = $this->getUser(Role::ROLE_ADMIN);

        // Test ACL
        yield [null, 5, [], 0, 401];
        yield [$this->getUser(Role::ROLE_CONTRIBUTOR), 5, [], 0, 403];

        // Invalid source field
        yield [
            $adminUser,
            105,
            [
                ['code' => 'new_brand_code', 'defaultLabel' => 'New brand'],
            ],
            5,
            400,
            "The source field doesn't exist.",
        ];

        // Non-select source field
        yield [
            $adminUser,
            5,
            [
                ['code' => 'new_brand_code', 'defaultLabel' => 'New brand'],
            ],
            5,
            400,
            'You can only add options to a source field of type "select".',
        ];

        // Incomplete data
        yield [
            $adminUser,
            9,
            [
                ['position' => 4],
            ],
            0,
            400,
            'A code value is required for source field option.',
        ];
        yield [
            $adminUser,
            9,
            [
                ['code' => 'new_option_code'],
            ],
            5,
            400,
            'The option new_option_code doesn\'t have a default label.',
        ];

        // With one option
        yield [
            $adminUser,
            9,
            [
                ['code' => 'new_brand_code', 'defaultLabel' => 'New brand'],
            ],
            5,
            200,
        ];
        // With multiple options
        yield [
            $adminUser,
            9,
            [
                ['code' => 'new_brand_code_2', 'defaultLabel' => 'New brand 2'],
                ['code' => 'new_brand_code_3', 'defaultLabel' => 'New brand 3'],
            ],
            7,
            200,
        ];
        // With new & updated options
        yield [
            $adminUser,
            9,
            [
                ['@id' => '/source_field_options/5', 'code' => 'new_brand_code', 'defaultLabel' => 'New brand Updated'],
                ['code' => 'new_brand_code_4', 'defaultLabel' => 'New brand 4'],
            ],
            8,
            200,
        ];
        // With updated option of another sourceField
        yield [
            $adminUser,
            12,
            [
                ['@id' => '/source_field_options/5', 'code' => 'new_brand_code', 'defaultLabel' => 'New brand Updated'],
            ],
            8,
            400,
            'The option 5 is not linked to the source field 12.',
        ];
        // With labels
        yield [
            $adminUser,
            9,
            [
                [
                    'code' => 'new_brand_code_6',
                    'defaultLabel' => 'New brand 6',
                    'labels' => [
                        ['localizedCatalog' => '/localized_catalogs/1', 'label' => 'Localized label brand 6'],
                    ],
                ],
                [
                    'code' => 'new_brand_code_7',
                    'defaultLabel' => 'New brand 7',
                    'labels' => [
                        ['localizedCatalog' => '/localized_catalogs/1', 'label' => 'Localized label1 brand 7'],
                    ],
                ],
            ],
            10,
            200,
        ];
        // With updated & new labels
        yield [
            $adminUser,
            9,
            [
                [
                    '@id' => '/source_field_options/10',
                    'code' => 'new_brand_code_7',
                    'defaultLabel' => 'New brand 7',
                    'labels' => [
                        [
                            '@id' => '/source_field_option_labels/2',
                            'localizedCatalog' => '/localized_catalogs/1',
                            'label' => 'Localized label1 brand 7 update',
                        ],
                        [
                            'localizedCatalog' => '/localized_catalogs/2',
                            'label' => 'Localized label2 brand 7',
                        ],
                    ],
                ],
            ],
            10,
            200,
        ];
    }
}
