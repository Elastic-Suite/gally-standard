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

namespace Gally\User\Tests\Api\Rest;

use Gally\Test\AbstractEntityTestWithUpdate;
use Gally\User\Constant\Role;
use Gally\User\Entity\User;

class UserTest extends AbstractEntityTestWithUpdate
{
    protected static function getFixtureFiles(): array
    {
        return [];
    }

    protected function getEntityClass(): string
    {
        return User::class;
    }

    public function createDataProvider(): iterable
    {
        $adminUser = $this->getUser(Role::ROLE_ADMIN);

        return [
            [$adminUser, ['email' => 'admin@example.com', 'isActive' => true, 'firstName' => 'John', 'lastName' => 'Doe', 'roles' => [Role::ROLE_ADMIN, Role::ROLE_CONTRIBUTOR]], 201],
            [$adminUser, ['email' => 'contributor@example.com', 'isActive' => true, 'firstName' => 'Alice', 'lastName' => 'Doe', 'roles' => [Role::ROLE_CONTRIBUTOR]], 201],
            [$adminUser, ['email' => 'admin+bis@example.com', 'isActive' => true, 'firstName' => 'John', 'lastName' => 'Bis', 'roles' => [Role::ROLE_ADMIN, Role::ROLE_CONTRIBUTOR]], 201],
            [$adminUser, ['email' => 'contributor+bis@example.com', 'isActive' => true, 'firstName' => 'Alice', 'lastName' => 'Bis', 'roles' => [Role::ROLE_CONTRIBUTOR]], 201],

            [$this->getUser(Role::ROLE_CONTRIBUTOR), ['email' => 'forbidden@example.com', 'isActive' => true, 'firstName' => 'Alice', 'lastName' => 'Doe', 'roles' => [Role::ROLE_CONTRIBUTOR]], 403],
            [null, ['email' => 'jwt@example.com', 'isActive' => true, 'firstName' => 'JWT Token not found', 'lastName' => 'Doe', 'roles' => [Role::ROLE_CONTRIBUTOR]], 401],

            [$adminUser, ['email' => 'contributor@example.com', 'isActive' => true, 'firstName' => 'Alice', 'lastName' => 'Doe', 'roles' => [Role::ROLE_CONTRIBUTOR]], 422, 'email: This email is already associated with another user.'],
            [$adminUser, [ 'isActive' => true,'firstName' => 'Alice', 'lastName' => 'Doe', 'roles' => [Role::ROLE_CONTRIBUTOR]], 422, 'email: This value should not be blank.'],
            [$adminUser, ['email' => 'error', 'isActive' => true, 'firstName' => 'Error', 'lastName' => 'Doe', 'roles' => [Role::ROLE_CONTRIBUTOR]], 422, 'email: This value is not a valid email address.'],
            [$adminUser, ['email' => str_repeat('abcd', 42) . '@examples.com', 'isActive' => true, 'firstName' => 'Error', 'lastName' => 'Doe', 'roles' => [Role::ROLE_CONTRIBUTOR]], 422, 'email: This value is too long. It should have 180 characters or less.'],

            [$adminUser, ['email' => 'error@example.com', 'isActive' => null, 'firstName' => 'Error', 'lastName' => 'Doe', 'roles' => [Role::ROLE_CONTRIBUTOR]], 400, 'The type of the "isActive" attribute must be "bool", "NULL" given.'],
            [$adminUser, ['email' => 'error@example.com', 'isActive' => '', 'firstName' => 'Error', 'lastName' => 'Doe', 'roles' => [Role::ROLE_CONTRIBUTOR]], 400, 'The type of the "isActive" attribute must be "bool", "string" given.'],

            [$adminUser, ['email' => 'error@example.com', 'isActive' => true, 'lastName' => 'Doe', 'roles' => [Role::ROLE_CONTRIBUTOR]], 422, 'firstName: This value should not be blank.'],
            [$adminUser, ['email' => 'error@example.com', 'isActive' => true, 'firstName' => '', 'lastName' => 'Doe', 'roles' => [Role::ROLE_CONTRIBUTOR]], 422, 'firstName: This value should not be blank.'],

            [$adminUser, ['email' => 'error@example.com', 'isActive' => true, 'firstName' => 'Error', 'roles' => [Role::ROLE_CONTRIBUTOR]], 422, 'lastName: This value should not be blank.'],
            [$adminUser, ['email' => 'error@example.com', 'isActive' => true, 'firstName' => 'Error', 'lastName' => '', 'roles' => [Role::ROLE_CONTRIBUTOR]], 422, 'lastName: This value should not be blank.'],

            [$adminUser, ['email' => 'error@example.com', 'isActive' => true, 'firstName' => 'Error', 'lastName' => 'Doe', 'roles' => []], 422, 'roles: This value should not be blank.'],
            [$adminUser, ['email' => 'error@example.com', 'isActive' => true, 'firstName' => 'Error', 'lastName' => 'Doe', 'roles' => ['ROLE_FAKE']], 422, 'roles: One or more of the given values is invalid.'],
        ];
    }

    public function getDataProvider(): iterable
    {
        $user = $this->getUser(Role::ROLE_ADMIN);

        return [
            [null, 3, ['id' => 1, 'email' => 'admin@example.com',], 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 1, ['id' => 1, 'email' => 'admin@example.com',], 403],
            [$user, 3, ['id' => 3, 'email' => 'admin@example.com', 'isActive' => true, 'firstName' => 'John', 'lastName' => 'Doe', 'roles' => [Role::ROLE_ADMIN, Role::ROLE_CONTRIBUTOR], 'dummyPassword' =>  '********'], 200],
            [$user, 7, [], 404],
        ];
    }

    public function deleteDataProvider(): iterable
    {
        $adminUser = $this->getUser(Role::ROLE_ADMIN);

        return [
            [null, 1, 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 1, 403],
            [$adminUser, 5, 204],
            [$adminUser, 6, 204],
            [$adminUser, 7, 404],
        ];
    }

    public function getCollectionDataProvider(): iterable
    {
        return [
            [null, 5, 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 5, 403],
            [$this->getUser(Role::ROLE_ADMIN), 4, 200],
        ];
    }

    public function patchUpdateDataProvider(): iterable
    {
        return [
            [null, 3, ['firstName' => 'John PATCH/PUT'], 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 3, ['firstName' => 'John PATCH/PUT'], 403],
            [$this->getUser(Role::ROLE_ADMIN), 3, ['firstName' => 'John PATCH/PUT'], 200],
        ];
    }
}
