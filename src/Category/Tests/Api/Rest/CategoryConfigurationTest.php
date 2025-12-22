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

namespace Gally\Category\Tests\Api\Rest;

use Gally\Catalog\Repository\CatalogRepository;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Category\Entity\Category;
use Gally\Test\AbstractEntityTestWithUpdate;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestToTest;
use Gally\User\Constant\Role;
use Gally\User\Entity\User;
use Symfony\Contracts\HttpClient\ResponseInterface;

class CategoryConfigurationTest extends AbstractEntityTestWithUpdate
{
    protected static function getFixtureFiles(): array
    {
        return [
            __DIR__ . '/../../fixtures/source_field.yaml',
            __DIR__ . '/../../fixtures/metadata.yaml',
            __DIR__ . '/../../fixtures/catalogs.yaml',
            __DIR__ . '/../../fixtures/categories.yaml',
        ];
    }

    protected function getEntityClass(): string
    {
        return Category\Configuration::class;
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(
        ?User $user,
        array $data,
        int $responseCode = 201,
        ?string $message = null,
        ?string $validRegex = null,
        array $files = [],
    ): void {
        if (isset($data['catalog'])) {
            $catalogRepository = static::getContainer()->get(CatalogRepository::class);
            $catalog = $catalogRepository->findOneBy(['code' => $data['catalog']]);
            $data['catalog'] = $this->getUri('catalogs', $catalog->getId());
        }
        if (isset($data['localizedCatalog'])) {
            $localizedCatalogRepository = static::getContainer()->get(LocalizedCatalogRepository::class);
            $localizedCatalog = $localizedCatalogRepository->findOneBy(['code' => $data['localizedCatalog']]);
            $data['localizedCatalog'] = $this->getUri('localized_catalogs', $localizedCatalog->getId());
        }
        $data['category'] = $this->getUri('categories', $data['category']);
        parent::testCreate($user, $data, $responseCode, $message, $validRegex);
    }

    public function createDataProvider(): iterable
    {
        $adminUser = $this->getUser(Role::ROLE_ADMIN);

        return [
            [
                null,
                ['category' => 'one', 'name' => 'One'],
                401,
            ],
            [
                $this->getUser(Role::ROLE_CONTRIBUTOR),
                ['category' => 'one', 'name' => 'One'],
            ],
            [
                $adminUser,
                ['category' => 'one', 'catalog' => 'b2c', 'name' => 'One'],
            ],
            [
                $adminUser,
                ['category' => 'one', 'catalog' => 'b2c', 'localizedCatalog' => 'b2c_fr', 'name' => 'One'],
            ],
            [
                $adminUser,
                ['category' => 'two', 'catalog' => 'b2c', 'localizedCatalog' => 'b2c_fr', 'name' => 'Two', 'defaultSorting' => 'name'],
            ],
            [
                $adminUser,
                ['category' => 'three', 'catalog' => 'b2c', 'localizedCatalog' => 'b2c_fr', 'name' => 'Three', 'defaultSorting' => 'invalidSort'],
                422,
                'defaultSorting: The option "invalidSort" is not a valid option for sorting.',
            ],
        ];
    }

    public function getDataProvider(): iterable
    {
        $user = $this->getUser(Role::ROLE_ADMIN);

        return [
            [null, 1, ['id' => 'One', 'name' => 'One'], 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 1, ['id' => 1, 'name' => 'One'], 200],
            [$user, 4, ['id' => 4, 'name' => 'Two'], 200],
            [$user, 10, [], 404],
        ];
    }

    public function deleteDataProvider(): iterable
    {
        $adminUser = $this->getUser(Role::ROLE_ADMIN);

        return [
            [null, 1, 405],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 1, 405],
            [$adminUser, 1, 405],
            [$adminUser, 10, 405],
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

    public function testGetWithContext(): void
    {
        $this->validateApiCall(
            new RequestToTest('GET', "{$this->getApiPath()}/category/one", null),
            new ExpectedResponse(401)
        );

        foreach ([$this->getUser(Role::ROLE_CONTRIBUTOR), $this->getUser(Role::ROLE_ADMIN)] as $user) {
            $this->validateApiCall(
                new RequestToTest('GET', "{$this->getApiPath()}/category/one", $user),
                new ExpectedResponse(
                    200,
                    function (ResponseInterface $response) {
                        $this->assertJsonContains(['name' => 'One']);
                    }
                )
            );
        }

        $this->validateApiCall(
            new RequestToTest('GET', "{$this->getApiPath()}/category/ten", $this->getUser(Role::ROLE_ADMIN)),
            new ExpectedResponse(404)
        );
    }

    public function patchUpdateDataProvider(): iterable
    {
        return [
            [null, 1, ['name' => 'One PATCH'], 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 1, ['name' => 'One PATCH'], 200],
            [$this->getUser(Role::ROLE_ADMIN), 1, ['name' => 'One PATCH Admin'], 200],
        ];
    }

    public function putUpdateDataProvider(): iterable
    {
        return [
            [null, 1, ['name' => 'One PUT'], 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 1, ['name' => 'One PUT'], 200],
            [$this->getUser(Role::ROLE_ADMIN), 1, ['name' => 'One PUT Admin'], 200],
        ];
    }
}
