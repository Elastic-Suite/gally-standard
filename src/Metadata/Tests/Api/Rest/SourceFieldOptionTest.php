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

use Gally\Metadata\Model\SourceFieldOption;
use Gally\Test\AbstractEntityTestWithUpdate;
use Gally\User\Constant\Role;

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

    /**
     * {@inheritDoc}
     */
    public function createDataProvider(): iterable
    {
        $adminUser = $this->getUser(Role::ROLE_ADMIN);

        return [
            [null, ['sourceField' => '/source_fields/4', 'code' => 'A', 'position' => 10, 'defaultLabel' => 'label'], 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), ['sourceField' => '/source_fields/4', 'code' => 'A', 'position' => 10, 'defaultLabel' => 'label'], 403],
            [$adminUser, ['sourceField' => '/source_fields/4', 'code' => 'A', 'position' => 10, 'defaultLabel' => 'label'], 201],
            [$adminUser, ['sourceField' => '/source_fields/4', 'code' => 'B', 'defaultLabel' => 'label'], 201],
            [$adminUser,
                [
                    'sourceField' => '/source_fields/4',
                    'code' => 'C',
                    'defaultLabel' => 'label',
                    'labels' => [
                        ['localizedCatalog' => '/localized_catalogs/1', 'label' => 'L\'option C'],
                        ['localizedCatalog' => '/localized_catalogs/2', 'label' => 'C option'],
                    ],
                ],
                201,
            ],
            [$adminUser, ['position' => 3, 'code' => 'A', 'defaultLabel' => 'label'], 422, 'sourceField: This value should not be blank.'],
            [$adminUser, ['sourceField' => '/source_fields/4', 'position' => 3, 'defaultLabel' => 'label'], 422, 'code: This value should not be blank.'],
            [$adminUser, ['sourceField' => '/source_fields/4', 'position' => 3, 'code' => 'D'], 422, 'defaultLabel: This value should not be blank.'],
            [$adminUser, ['sourceField' => '/source_fields/4', 'code' => 'A', 'position' => 3, 'defaultLabel' => 'label'], 422, 'sourceField: An option with this code is already defined for this sourceField.'],
        ];
    }

    protected function getJsonCreationValidation(array $expectedData): array
    {
        foreach ($expectedData['labels'] ?? [] as $index => $label) {
            // Remove localized catalog in labels data because localized catalog are include as sub entity in response.
            // @see api/packages/gally-standard/src/Catalog/Model/LocalizedCatalog.php:55
            unset($expectedData['labels'][$index]['localizedCatalog']);
        }

        return parent::getJsonCreationValidation($expectedData);
    }

    /**
     * {@inheritDoc}
     */
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
            [$adminUser, 2, 204],
            [$adminUser, 10, 404],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getCollectionDataProvider(): iterable
    {
        return [
            [null, 5, 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 5, 200],
            [$this->getUser(Role::ROLE_ADMIN), 5, 200],
        ];
    }

    public function patchUpdateDataProvider(): iterable
    {
        return [
            [null, 1, ['defaultLabel' => 'label PATCH/PUT'], 405],
        ];
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
                        ['localizedCatalog' => '/localized_catalogs/1', 'label' => 'L\'option A'],
                    ],
                ],
                200,
            ],
            [
                $this->getUser(Role::ROLE_ADMIN),
                6,
                [ // Add multiple labels
                    'labels' => [
                        ['localizedCatalog' => '/localized_catalogs/1', 'label' => 'L\'option B'],
                        ['localizedCatalog' => '/localized_catalogs/2', 'label' => 'B Option'],
                    ],
                ],
                200,
            ],
            [ // Add and update multiple labels
                $this->getUser(Role::ROLE_ADMIN),
                5,
                [
                    'labels' => [
                        ['@id' => '/source_field_option_labels/3', 'localizedCatalog' => '/localized_catalogs/1', 'label' => 'L\'option A Updated'],
                        ['localizedCatalog' => '/localized_catalogs/2', 'label' => 'A option'],
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
}
