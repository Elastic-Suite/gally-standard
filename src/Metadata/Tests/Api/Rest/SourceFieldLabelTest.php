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

use Gally\Metadata\Entity\SourceFieldLabel;
use Gally\Test\AbstractEntityTestWithUpdate;
use Gally\User\Constant\Role;

class SourceFieldLabelTest extends AbstractEntityTestWithUpdate
{
    protected static function getFixtureFiles(): array
    {
        return [
            __DIR__ . '/../../fixtures/catalogs.yaml',
            __DIR__ . '/../../fixtures/source_field_label.yaml',
            __DIR__ . '/../../fixtures/source_field.yaml',
            __DIR__ . '/../../fixtures/metadata.yaml',
        ];
    }

    protected static function getEntityClass(): string
    {
        return SourceFieldLabel::class;
    }

    public function createDataProvider(): iterable
    {
        $adminUser = $this->getUser(Role::ROLE_ADMIN);

        return [
            [null, ['localizedCatalog' => $this->getUri('localized_catalogs', '1'), 'sourceField' => $this->getUri('source_fields', '19'), 'label' => 'Prix'], 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), ['localizedCatalog' => $this->getUri('localized_catalogs', '1'), 'sourceField' => $this->getUri('source_fields', '19'), 'label' => 'Prix'], 403],
            [$adminUser, ['localizedCatalog' => $this->getUri('localized_catalogs', '1'), 'sourceField' => $this->getUri('source_fields', '19'), 'label' => 'Prix'], 201],
            [$adminUser, ['localizedCatalog' => $this->getUri('localized_catalogs', '2'), 'sourceField' => $this->getUri('source_fields', '19'), 'label' => 'Price'], 201],
            [$adminUser, ['localizedCatalog' => $this->getUri('localized_catalogs', '1'), 'sourceField' => $this->getUri('source_fields', '15'), 'label' => 'Nom'], 201],
            [$adminUser, ['localizedCatalog' => $this->getUri('localized_catalogs', '2'), 'sourceField' => $this->getUri('source_fields', '15'), 'label' => 'Name'], 201],
            [
                $adminUser,
                ['localizedCatalog' => $this->getUri('localized_catalogs', '1'), 'sourceField' => $this->getUri('source_fields', '22')],
                422,
                'label: This value should not be blank.',
            ],
            [
                $adminUser,
                ['localizedCatalog' => $this->getUri('localized_catalogs', '1'), 'sourceField' => $this->getUri('source_fields', '15'), 'label' => 'Titre'],
                422,
                'sourceField: A label is already defined for this field and this localized catalog.',
            ],
            [
                $adminUser,
                ['sourceField' => $this->getUri('source_fields', '22'), 'label' => 'Marque'],
                422,
                'localizedCatalog: This value should not be blank.',
            ],
            [
                $adminUser,
                ['localizedCatalog' => $this->getUri('localized_catalogs', '1'), 'label' => 'Marque'],
                422,
                'sourceField: This value should not be blank.',
            ],
            [
                $adminUser,
                ['localizedCatalog' => $this->getUri('localized_catalogs', 'NotExist'), 'sourceField' => $this->getUri('source_fields', '22'), 'label' => 'Marque'],
                400,
                'Item not found for "' . $this->getUri('localized_catalogs', 'NotExist') . '".',
            ],
            [
                $adminUser,
                ['localizedCatalog' => $this->getUri('localized_catalogs', '1'), 'sourceField' => $this->getUri('source_fields', 'NotExist'), 'label' => 'Marque'],
                400,
                'Item not found for "' . $this->getUri('source_fields', 'NotExist') . '".',
            ],
        ];
    }

    public function getDataProvider(): iterable
    {
        $user = $this->getUser(Role::ROLE_CONTRIBUTOR);

        return [
            [null, 1, ['id' => 1, 'label' => 'Nom'], 401],
            [$user, 1, ['id' => 1, 'label' => 'Nom'], 200],
            [$this->getUser(Role::ROLE_ADMIN), 1, ['id' => 1, 'label' => 'Nom'], 200],
            [$user, 2, ['id' => 2, 'label' => 'Name'], 200],
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
            [$adminUser, 10, 404],
        ];
    }

    public function getCollectionDataProvider(): iterable
    {
        return [
            [null, 4, 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 4, 200],
            [$this->getUser(Role::ROLE_ADMIN), 4, 200],
        ];
    }

    public function patchUpdateDataProvider(): iterable
    {
        return [
            [null, 1, ['label' => 'Nom PATCH/PUT'], 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 1,  ['label' => 'Nom PATCH/PUT'], 403],
            [$this->getUser(Role::ROLE_ADMIN), 1, ['label' => 'Nom PATCH/PUT'], 200],
        ];
    }
}
