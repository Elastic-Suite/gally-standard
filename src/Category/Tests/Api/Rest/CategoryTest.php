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

use Gally\Category\Model\Category;
use Gally\Test\AbstractEntityTestWithUpdate;
use Gally\User\Constant\Role;
use Gally\User\Model\User;

class CategoryTest extends AbstractEntityTestWithUpdate
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
        return Category::class;
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(
        ?User $user,
        array $data,
        int $responseCode = 201,
        ?string $message = null,
        ?string $validRegex = null
    ): void {
        // Category can't be created via api.
        $this->assertTrue(true);
    }

    public function createDataProvider(): iterable
    {
        return [
            [null, ['id' => 'one', 'name' => 'One'], 405],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), ['id' => 'one', 'name' => 'One'], 405],
            [$this->getUser(Role::ROLE_ADMIN), ['id' => 'one', 'name' => 'One'], 405],
        ];
    }

    public function getDataProvider(): iterable
    {
        $user = $this->getUser(Role::ROLE_ADMIN);

        return [
            [null, 'one', ['id' => 'one'], 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 'one', ['id' => 'one'], 200],
            [$user, 'one', ['id' => 'one'], 200],
            [$user, 'two', ['id' => 'two'], 200],
            [$user, 'missing', [], 404],
        ];
    }

    public function deleteDataProvider(): iterable
    {
        $adminUser = $this->getUser(Role::ROLE_ADMIN);

        return [
            [null, 'one', 405],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 'one', 405],
            [$adminUser, 'one', 405],
            [$adminUser, 'missing', 405],
        ];
    }

    public function getCollectionDataProvider(): iterable
    {
        return [
            [null, 4, 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 5, 200],
            [$this->getUser(Role::ROLE_ADMIN), 5, 200],
        ];
    }

    public function patchUpdateDataProvider(): iterable
    {
        $validRegex = '~^' . $this->getApiPath() . '/\S+$~';

        return [
            [null, 'one', ['level' => 2], 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 'one', ['level' => 1], 200, null, $validRegex],
            [$this->getUser(Role::ROLE_ADMIN), 'one', ['level' => 1], 200, null, $validRegex],
        ];
    }
}
