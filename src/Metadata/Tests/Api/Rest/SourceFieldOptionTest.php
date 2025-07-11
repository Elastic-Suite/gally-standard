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

use Gally\Metadata\Entity\SourceFieldOption;
use Gally\Metadata\Repository\SourceFieldOptionLabelRepository;
use Gally\Metadata\Repository\SourceFieldOptionRepository;
use Gally\Metadata\Repository\SourceFieldRepository;
use Gally\Test\AbstractEntityTestWithUpdate;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestToTest;
use Gally\User\Constant\Role;
use Gally\User\Entity\User;
use Symfony\Contracts\HttpClient\ResponseInterface;

class SourceFieldOptionTest extends AbstractEntityTestWithUpdate
{
    protected static function getFixtureFiles(): array
    {
        return [
            __DIR__ . '/../../fixtures/catalogs.yaml',
            __DIR__ . '/../../fixtures/source_field_option.yaml',
            __DIR__ . '/../../fixtures/source_field.yaml',
            __DIR__ . '/../../fixtures/metadata.yaml',
        ];
    }

    protected function getEntityClass(): string
    {
        return SourceFieldOption::class;
    }

    public function createDataProvider(): iterable
    {
        $adminUser = $this->getUser(Role::ROLE_ADMIN);

        return [
            [null, ['sourceField' => $this->getUri('source_fields', '4'), 'code' => 'A', 'position' => 10, 'defaultLabel' => 'label'], 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), ['sourceField' => $this->getUri('source_fields', '4'), 'code' => 'A', 'position' => 10, 'defaultLabel' => 'label'], 403],
            [$adminUser, ['sourceField' => $this->getUri('source_fields', '4'), 'code' => 'A', 'position' => 10, 'defaultLabel' => 'label'], 201],
            [$adminUser, ['sourceField' => $this->getUri('source_fields', '4'), 'code' => 'B', 'defaultLabel' => 'label'], 201],
            [$adminUser,
                [
                    'sourceField' => $this->getUri('source_fields', '4'),
                    'code' => 'C',
                    'defaultLabel' => 'label',
                    'labels' => [
                        ['localizedCatalog' => $this->getUri('localized_catalogs', '1'), 'label' => 'L\'option C'],
                        ['localizedCatalog' => $this->getUri('localized_catalogs', '2'), 'label' => 'C option'],
                    ],
                ],
                201,
            ],
            [$adminUser, ['position' => 3, 'code' => 'A', 'defaultLabel' => 'label'], 422, 'sourceField: This value should not be blank.'],
            [$adminUser, ['sourceField' => $this->getUri('source_fields', '4'), 'position' => 3, 'defaultLabel' => 'label'], 422, 'code: This value should not be blank.'],
            [$adminUser, ['sourceField' => $this->getUri('source_fields', '4'), 'position' => 3, 'code' => 'D'], 422, 'defaultLabel: This value should not be blank.'],
            [$adminUser, ['sourceField' => $this->getUri('source_fields', '4'), 'code' => 'A', 'position' => 3, 'defaultLabel' => 'label'], 422, 'sourceField: An option with this code is already defined for this sourceField.'],
        ];
    }

    protected function getJsonCreationValidation(array $expectedData): array
    {
        unset($expectedData['labels']);

        return parent::getJsonCreationValidation($expectedData);
    }

    public function getDataProvider(): iterable
    {
        $user = $this->getUser(Role::ROLE_CONTRIBUTOR);

        return [
            [null, 1, ['id' => 1], 401],
            [$user, 1, ['id' => 1], 200],
            [$this->getUser(Role::ROLE_ADMIN), 1, ['id' => 1], 200],
            [$user, 2, ['id' => 2], 200],
            [$user, 10, [], 404],
        ];
    }

    public function deleteDataProvider(): iterable
    {
        $adminUser = $this->getUser(Role::ROLE_ADMIN);

        return [
            [null, 1, 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 1, 403],
            [$adminUser, 1, 204],
            [$adminUser, 2, 204],
            [$adminUser, 99, 404],
        ];
    }

    public function getCollectionDataProvider(): iterable
    {
        return [
            [null, 11, 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 11, 200],
            [$this->getUser(Role::ROLE_ADMIN), 11, 200],
        ];
    }

    public function patchUpdateDataProvider(): iterable
    {
        return [
            [null, 1, ['defaultLabel' => 'label PATCH/PUT'], 405],
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

        /** @var SourceFieldOptionLabelRepository $sourceFieldOptionLabelRepository */
        $sourceFieldOptionLabelRepository = static::getContainer()->get(SourceFieldOptionLabelRepository::class);
        $labels = $sourceFieldOptionLabelRepository->findBy(['sourceFieldOption' => $id]);
        $this->assertCount(\count($data['labels'] ?? []), $labels);

        return $response;
    }

    public function putUpdateDataProvider(): iterable
    {
        return [
            [null, 1, ['defaultLabel' => 'label PATCH/PUT'], 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 1,  ['defaultLabel' => 'label PATCH/PUT'], 403],
            [$this->getUser(Role::ROLE_ADMIN), 1, ['defaultLabel' => 'label PATCH/PUT'], 200],
            [ // Add label
                $this->getUser(Role::ROLE_ADMIN),
                5,
                [
                    'labels' => [
                        ['localizedCatalog' => $this->getUri('localized_catalogs', '1'), 'label' => 'L\'option A'],
                    ],
                ],
                200,
            ],
            [
                $this->getUser(Role::ROLE_ADMIN),
                6,
                [ // Add multiple labels
                    'labels' => [
                        ['localizedCatalog' => $this->getUri('localized_catalogs', '1'), 'label' => 'L\'option B'],
                        ['localizedCatalog' => $this->getUri('localized_catalogs', '2'), 'label' => 'B Option'],
                    ],
                ],
                200,
            ],
            [ // Add and update multiple labels
                $this->getUser(Role::ROLE_ADMIN),
                5,
                [
                    'labels' => [
                        ['localizedCatalog' => $this->getUri('localized_catalogs', '1'), 'label' => 'L\'option A Updated'],
                        ['localizedCatalog' => $this->getUri('localized_catalogs', '2'), 'label' => 'A option'],
                    ],
                ],
                200,
            ],
        ];
    }

    protected function getJsonUpdateValidation(array $expectedData): array
    {
        unset($expectedData['labels']);

        return parent::getJsonUpdateValidation($expectedData);
    }

    /**
     * @depends testPutUpdate
     *
     * @dataProvider bulkDataProvider
     */
    public function testBulk(
        ?User $user,
        array $options,
        array $expectedOptionCountBySourceField,
        array $expectedResponseData,
        int $responseCode,
        ?string $message = null
    ): void {
        $request = new RequestToTest('POST', "{$this->getApiPath()}/bulk", $user, $options);
        $expectedResponse = new ExpectedResponse(
            $responseCode,
            function (ResponseInterface $response) use ($expectedOptionCountBySourceField, $expectedResponseData, $options) {
                $this->assertJsonContains(['hydra:member' => $expectedResponseData]);
                $sourceFieldRepository = static::getContainer()->get(SourceFieldRepository::class);
                $optionRepository = static::getContainer()->get(SourceFieldOptionRepository::class);
                foreach ($options as $optionData) {
                    $sourceFieldId = (int) str_replace($this->getRoute('source_fields') . '/', '', $optionData['sourceField']);
                    $sourceField = $sourceFieldRepository->find($sourceFieldId);
                    $this->assertCount($expectedOptionCountBySourceField[$sourceField->getId()], $sourceField->getOptions());

                    /** @var SourceFieldOption $option */
                    $option = $optionRepository->findOneBy(['code' => $optionData['code'], 'sourceField' => $sourceFieldId]);
                    $this->assertCount(\count($optionData['labels'] ?? []), $option->getLabels());
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
        yield [null, [], [], [], 401];
        yield [$this->getUser(Role::ROLE_CONTRIBUTOR), [], [], [], 403];

        // Invalid source field
        yield [
            $adminUser,
            [
                ['sourceField' => $this->getUri('source_fields', '105'), 'code' => 'new_brand_code', 'defaultLabel' => 'New brand'],
            ],
            [105 => 5],
            [],
            400,
            'Option #0: Item not found for "' . $this->getUri('source_fields', '105') . '".',
        ];

        // Incomplete/Invalid data
        yield [
            $adminUser,
            [
                ['position' => 4],
                ['sourceField' => $this->getUri('source_fields', '9'), 'position' => 4],
                ['sourceField' => $this->getUri('source_fields', '9'), 'code' => 'new_option_code'],
                ['sourceField' => $this->getUri('source_fields', '5'), 'code' => 'new_brand_code', 'defaultLabel' => 'New brand'],
                [
                    'sourceField' => $this->getUri('source_fields', '9'),
                    'code' => 'new_brand_code',
                    'defaultLabel' => 'New brand',
                    'labels' => [
                        ['localizedCatalog' => $this->getUri('localized_catalogs', '1000'), 'label' => 'Label brand on fake localized catalog'],
                    ],
                ],
            ],
            [],
            [],
            400,
            'Option #0: A sourceField value is required for source field option. ' .
            'Option #1: A code value is required for source field option. ' .
            'Option #2: A defaultLabel value is required for source field option. ' .
            'Option #3: You can only add options to a source field of type "select". ' .
            'Option #4: Item not found for "' . $this->getUri('localized_catalogs', '1000') . '".',
        ];

        // With one option
        yield [
            $adminUser,
            [
                ['sourceField' => $this->getUri('source_fields', '9'), 'code' => 'new_brand_code', 'defaultLabel' => 'New brand'],
            ],
            [9 => 5],
            [
                0 => ['defaultLabel' => 'New brand'],
            ],
            200,
        ];
        // With multiple valid options and one invalid option
        yield [
            $adminUser,
            [
                ['sourceField' => $this->getUri('source_fields', '9'), 'code' => 'new_brand_code_2', 'defaultLabel' => 'New brand 2'],
                ['sourceField' => $this->getUri('source_fields', '9'), 'code' => 'new_brand_code_3', 'defaultLabel' => 'New brand 3'],
                ['sourceField' => $this->getUri('source_fields', '12'), 'defaultLabel' => 'New material 0'],
                ['sourceField' => $this->getUri('source_fields', '12'), 'code' => 'new_material_code_1', 'defaultLabel' => 'New material 1'],
            ],
            [
                9 => 7,
                12 => 1,
            ],
            [
                2 => ['code' => 'new_material_code_1'],
            ],
            400,
            'Option #2: A code value is required for source field option.',
        ];
        // With new & updated options
        yield [
            $adminUser,
            [
                ['sourceField' => $this->getUri('source_fields', '9'), 'code' => 'new_brand_code', 'defaultLabel' => 'New brand Updated'],
                ['sourceField' => $this->getUri('source_fields', '9'), 'code' => 'new_brand_code_4', 'defaultLabel' => 'New brand 4'],
            ],
            [9 => 8],
            [
                0 => ['defaultLabel' => 'New brand Updated'],
            ],
            200,
        ];
        // With labels on new option and existing option
        yield [
            $adminUser,
            [
                [
                    'sourceField' => $this->getUri('source_fields', '9'),
                    'code' => 'new_brand_code_4',
                    'defaultLabel' => 'New brand 4',
                    'labels' => [
                        ['localizedCatalog' => $this->getUri('localized_catalogs', '1'), 'label' => 'Localized label brand 4'],
                    ],
                ],
                [
                    'sourceField' => $this->getUri('source_fields', '9'),
                    'code' => 'new_brand_code_5',
                    'defaultLabel' => 'New brand 5',
                    'labels' => [
                        ['localizedCatalog' => $this->getUri('localized_catalogs', '1'), 'label' => 'Localized label1 brand 5'],
                    ],
                ],
            ],
            [9 => 9],
            [
                0 => [
                    'defaultLabel' => 'New brand 4',
                ],
                1 => [
                    'defaultLabel' => 'New brand 5',
                ],
            ],
            200,
        ];
        // With updated & new labels
        yield [
            $adminUser,
            [
                [
                    'sourceField' => $this->getUri('source_fields', '9'),
                    'code' => 'new_brand_code_4',
                    'defaultLabel' => 'New brand 4',
                    'labels' => [
                        [
                            'localizedCatalog' => $this->getUri('localized_catalogs', '1'),
                            'label' => 'Localized label1 brand 4 update',
                        ],
                        [
                            'localizedCatalog' => $this->getUri('localized_catalogs', '2'),
                            'label' => 'Localized label2 brand 4',
                        ],
                    ],
                ],
                [
                    'sourceField' => $this->getUri('source_fields', '9'),
                    'code' => 'new_brand_code_5',
                    'defaultLabel' => 'New brand 5',
                    'labels' => [
                        [
                            'localizedCatalog' => $this->getUri('localized_catalogs', '2'),
                            'label' => 'Localized label2 brand 5',
                        ],
                    ],
                ],
                [
                    'sourceField' => $this->getUri('source_fields', '12'),
                    'code' => 'new_material_code_1',
                    'defaultLabel' => 'New Material 1',
                    'labels' => [
                        [
                            'localizedCatalog' => $this->getUri('localized_catalogs', '1'),
                            'label' => 'Localized label1 material 1',
                        ],
                    ],
                ],
            ],
            [
                9 => 9,
                12 => 1,
            ],
            [
                0 => [
                    'defaultLabel' => 'New brand 4',
                ],
                1 => [
                    'defaultLabel' => 'New brand 5',
                ],
                2 => [
                    'defaultLabel' => 'New Material 1',
                ],
            ],
            200,
        ];
    }
}
