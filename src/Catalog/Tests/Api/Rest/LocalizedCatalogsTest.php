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

namespace Gally\Catalog\Tests\Api\Rest;

use Gally\Catalog\Entity\LocalizedCatalog;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Test\AbstractEntityTestWithUpdate;
use Gally\User\Constant\Role;

class LocalizedCatalogsTest extends AbstractEntityTestWithUpdate
{
    protected static function getFixtureFiles(): array
    {
        return [
            __DIR__ . '/../../fixtures/catalogs.yaml',
            __DIR__ . '/../../fixtures/localized_catalogs.yaml',
        ];
    }

    protected function getEntityClass(): string
    {
        return LocalizedCatalog::class;
    }

    public function createDataProvider(): iterable
    {
        $adminUser = $this->getUser(Role::ROLE_ADMIN);

        return [
            [$adminUser, ['catalog' => $this->getUri('catalogs', '1'), 'code' => 'valid_code', 'name' => 'B2C French catalog', 'locale' => 'fr_FR', 'currency' => 'EUR'], 201],
            [$adminUser, ['catalog' => $this->getUri('catalogs', '1'), 'code' => 'empty_name', 'name' => '', 'locale' => 'en_US', 'currency' => 'USD'], 201],
            [$adminUser, ['catalog' => $this->getUri('catalogs', '1'), 'code' => 'missing_name', 'locale' => 'fr_FR', 'currency' => 'EUR'], 201],
            [null, ['catalog' => $this->getUri('catalogs', '1'), 'code' => 'valid_code', 'name' => 'Unauthorized user', 'locale' => 'fr_FR', 'currency' => 'EUR'], 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), ['catalog' => $this->getUri('catalogs', '1'), 'code' => 'valid_code', 'name' => 'Unauthorized user', 'locale' => 'fr_FR', 'currency' => 'EUR'], 403],
            [$adminUser, ['code' => 'no_catalog', 'name' => 'B2C French catalog', 'locale' => 'fr_FR', 'currency' => 'EUR'], 422, 'catalog: This value should not be blank.'],
            [$adminUser, ['catalog' => $this->getUri('catalogs', '2'), 'code' => 'valid_code', 'locale' => 'fr_FR', 'currency' => 'EUR', 'name' => 'Empty Code'], 422, 'locale: This code and locale couple already exists.'],
            [$adminUser, ['catalog' => $this->getUri('catalogs', '1'), 'code' => '', 'locale' => 'en_US', 'currency' => 'USD', 'name' => 'Empty Code'], 422, 'code: This value should not be blank.'],
            [$adminUser, ['catalog' => $this->getUri('catalogs', '1'), 'code' => '', 'locale' => 'en_US', 'currency' => 'USD'], 422, 'code: This value should not be blank.'],
            [$adminUser, ['catalog' => $this->getUri('catalogs', '1'), 'locale' => 'en_US', 'currency' => 'USD', 'name' => 'Missing Code'], 422, 'code: This value should not be blank.'],
            [$adminUser, ['catalog' => $this->getUri('catalogs', '1'), 'code' => 'missing_locale', 'currency' => 'EUR'], 422, 'locale: This value should not be blank.'],
            [$adminUser, ['catalog' => $this->getUri('catalogs', '1'), 'code' => 'cat_1_invalid', 'locale' => 'fr-fr', 'currency' => 'EUR'], 422, 'locale: This value is not valid.'],
            [$adminUser, ['catalog' => $this->getUri('catalogs', '1'), 'code' => 'cat_1_invalid', 'locale' => 'strin', 'currency' => 'EUR'], 422, "locale: This value is not valid.\nlocale: This value is not a valid locale."],
            [$adminUser, ['catalog' => $this->getUri('catalogs', '1'), 'code' => 'cat_1_invalid', 'locale' => 'too_long_locale', 'currency' => 'EUR'], 422, "locale: This value is not valid.\nlocale: This value should have exactly 5 characters.\nlocale: This value is not a valid locale."],
            [$adminUser, ['catalog' => $this->getUri('catalogs', '1'), 'code' => 'cat_1_invalid', 'locale' => 'a', 'currency' => 'EUR'], 422, "locale: This value is not valid.\nlocale: This value should have exactly 5 characters.\nlocale: This value is not a valid locale."],
            [$adminUser, ['catalog' => $this->getUri('catalogs', '1'), 'code' => 'cat_1_invalid', 'locale' => 'abc', 'currency' => 'EUR'], 422, "locale: This value is not valid.\nlocale: This value should have exactly 5 characters.\nlocale: This value is not a valid locale."],
            [$adminUser, ['catalog' => $this->getUri('catalogs', '1'), 'code' => 'cat_1_invalid', 'locale' => 'ep_EP', 'currency' => 'EUR'], 422, 'locale: This value is not a valid locale.'],
            [$adminUser, ['catalog' => $this->getUri('catalogs', '1'), 'code' => 'cat_1_invalid', 'locale' => 'fr_FR', 'currency' => 'ZZZ'], 422, 'currency: This value is not a valid currency.'],
        ];
    }

    public function getDataProvider(): iterable
    {
        $user = $this->getUser(Role::ROLE_CONTRIBUTOR);

        return [
            // The first created localized catalog should have been elected as the default one,
            // this is why we test that the parameter isDefault is set to true even if we haven't specified it.
            [$user, 1, ['id' => 1, 'code' => 'b2c_fr', 'locale' => 'fr_FR', 'currency' => 'EUR', 'name' => 'B2C French Store View', 'isDefault' => true, 'localName' => 'Français (France)'], 200, 'fr_FR'],
            [null, 5, ['id' => 5, 'code' => 'empty_name', 'locale' => 'en_US', 'currency' => 'USD', 'localName' => 'English (United States)', 'isDefault' => false], 200, 'en_US'],
            [$user, 5, ['id' => 5, 'code' => 'empty_name', 'locale' => 'en_US', 'currency' => 'USD', 'localName' => 'English (United States)', 'isDefault' => false], 200, 'en_US'],
            [$this->getUser(Role::ROLE_ADMIN), 5, ['id' => 5, 'code' => 'empty_name', 'locale' => 'en_US', 'currency' => 'USD', 'localName' => 'English (United States)', 'isDefault' => false], 200, 'en_US'],
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
            [$adminUser, 5, 204],
            [$adminUser, 10, 404],
        ];
    }

    public function getCollectionDataProvider(): iterable
    {
        return [
            [null, 4, 200],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 4, 200],
            [$this->getUser(Role::ROLE_ADMIN), 4, 200],
        ];
    }

    public function patchUpdateDataProvider(): iterable
    {
        return [
            [null, 1, ['name' => 'B2C French Store View PATCH/PUT'], 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 1, ['name' => 'B2C French Store View PATCH/PUT'], 403],
            [$this->getUser(Role::ROLE_ADMIN), 1, ['name' => 'B2C French Store View PATCH/PUT'], 200],
        ];
    }

    /**
     * If a localized catalog is already "default", if we try to update it,  it stays default ?
     *
     * @depends testDelete
     */
    public function testUpdateIsDefault()
    {
        $user = $this->getUser(Role::ROLE_ADMIN);
        $this->update('PUT', $user, 2, ['isDefault' => true], 200, ['Content-Type' => 'application/ld+json']);

        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $entityManager->clear();

        $localizedCatalog = static::getContainer()->get(LocalizedCatalogRepository::class)->find(2);
        $this->assertTrue($localizedCatalog->getIsDefault());
    }
}
