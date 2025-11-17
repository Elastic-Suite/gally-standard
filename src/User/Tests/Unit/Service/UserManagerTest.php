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

namespace Gally\User\Tests\Unit\Service;

use Doctrine\ORM\EntityNotFoundException;
use Gally\Test\AbstractTestCase;
use Gally\User\Constant\Role;
use Gally\User\Entity\User;
use Gally\User\Repository\UserRepository;
use Gally\User\Service\UserManager;

class UserManagerTest extends AbstractTestCase
{
    private static string $userFirstName = 'John';
    private static string $userLastName = 'Doe';
    private static string $userEmail = 'user@example.com';
    private static array $userRoles = [Role::ROLE_CONTRIBUTOR];
    private static string $userPassword = 'Gally123';
    private static bool $userIsActive = true;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::loadFixture([]);
    }

    public function testCreate(): void
    {
        $userManager = static::getContainer()->get(UserManager::class);
        $userRepository = static::getContainer()->get(UserRepository::class);
        $userManager->create(self::$userFirstName, self::$userLastName, self::$userEmail, self::$userRoles, self::$userPassword, self::$userIsActive);
        $user = $userRepository->findOneBy(['email' => self::$userEmail]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(self::$userEmail, $user->getEmail());
        $this->assertEquals(self::$userRoles, $user->getRoles());
    }

    /**
     * @depends testCreate
     */
    public function testisUserExists()
    {
        $userManager = static::getContainer()->get(UserManager::class);
        $this->assertTrue($userManager->isUserExists(self::$userEmail));
        $this->assertFalse($userManager->isUserExists('fake_' . self::$userEmail));
    }

    public function testGetRoles()
    {
        $userManager = static::getContainer()->get(UserManager::class);
        $this->assertEquals(Role::ROLES, $userManager->getRoles());
    }

    public function testGetFakeRoles()
    {
        $userManager = static::getContainer()->get(UserManager::class);
        $fakeRole = 'ROLE_FAKE';
        $this->assertEquals([$fakeRole], $userManager->getFakeRoles([$fakeRole, Role::ROLE_ADMIN, Role::ROLE_CONTRIBUTOR]));
    }

    /**
     * @depends testCreate
     */
    public function testFailureUpdate(): void
    {
        // User not exists.
        $userManager = static::getContainer()->get(UserManager::class);
        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage(\sprintf("The user with the email 'fake_%s' was not found", self::$userEmail));
        $userManager->update('fake_' . self::$userEmail, null, null, self::$userEmail, null, null);
    }

    /**
     * @depends testCreate
     */
    public function testUpdate(): void
    {
        $userManager = static::getContainer()->get(UserManager::class);
        $userRepository = static::getContainer()->get(UserRepository::class);
        $password = 'Gally123!';
        $newFirstName = 'Tony';
        $newLastName = 'Dark';
        $newEmail = 'updated_user@example.com';
        $newRoles = [Role::ROLE_ADMIN, Role::ROLE_CONTRIBUTOR];
        $newPassword = 'NewGally123!';
        $newIsActive = false;

        $user = $userRepository->findOneBy(['email' => self::$userEmail]);
        $this->assertInstanceOf(User::class, $user);
        $passwordHash = $user->getPassword();

        // Change password.
        $userManager->update(self::$userEmail, null, null, null, null, $newPassword);
        $newPasswordHash = $user->getPassword();
        $this->assertEquals(self::$userFirstName, $user->getFirstName());
        $this->assertEquals(self::$userLastName, $user->getLastName());
        $this->assertEquals(self::$userEmail, $user->getEmail());
        $this->assertEquals(self::$userRoles, $user->getRoles());
        $this->assertNotEquals($passwordHash, $newPasswordHash);
        $this->assertEquals(self::$userIsActive, $user->getIsActive());

        // Change first name.
        $userManager->update(self::$userEmail, $newFirstName, null, null, null, null);
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($newFirstName, $user->getFirstName());
        $this->assertEquals(self::$userLastName, $user->getLastName());
        $this->assertEquals(self::$userEmail, $user->getEmail());
        $this->assertEquals(self::$userRoles, $user->getRoles());
        $this->assertEquals($newPasswordHash, $user->getPassword());
        $this->assertEquals(self::$userIsActive, $user->getIsActive());

        // Change last name.
        $userManager->update(self::$userEmail, null, $newLastName, null, null, null);
        $this->assertEquals($newFirstName, $user->getFirstName());
        $this->assertEquals($newLastName, $user->getLastName());
        $this->assertEquals(self::$userEmail, $user->getEmail());
        $this->assertEquals(self::$userRoles, $user->getRoles());
        $this->assertEquals($newPasswordHash, $user->getPassword());
        $this->assertEquals(self::$userIsActive, $user->getIsActive());

        // Change roles.
        $userManager->update(self::$userEmail, null, null, null, $newRoles, null);
        $this->assertEquals($newFirstName, $user->getFirstName());
        $this->assertEquals($newLastName, $user->getLastName());
        $this->assertEquals(self::$userEmail, $user->getEmail());
        $this->assertEquals($newRoles, $user->getRoles());
        $this->assertEquals($newPasswordHash, $user->getPassword());
        $this->assertEquals(self::$userIsActive, $user->getIsActive());

        // Change is active.
        $userManager->update(self::$userEmail, null, null, null, null, null, $newIsActive);
        $this->assertEquals($newFirstName, $user->getFirstName());
        $this->assertEquals($newLastName, $user->getLastName());
        $this->assertEquals(self::$userEmail, $user->getEmail());
        $this->assertEquals($newRoles, $user->getRoles());
        $this->assertEquals($newPasswordHash, $user->getPassword());
        $this->assertEquals($newIsActive, $user->getIsActive());

        // Change email.
        $userManager->update(self::$userEmail, null, null, $newEmail, null, null);
        $this->assertEquals($newFirstName, $user->getFirstName());
        $this->assertEquals($newLastName, $user->getLastName());
        $this->assertEquals($newEmail, $user->getEmail());
        $this->assertEquals($newRoles, $user->getRoles());
        $this->assertEquals($newPasswordHash, $user->getPassword());
        $this->assertEquals($newIsActive, $user->getIsActive());

        // Change all.
        $userManager->update($newEmail, self::$userFirstName, self::$userLastName, self::$userEmail, self::$userRoles, $password, self::$userIsActive);
        $this->assertEquals(self::$userFirstName, $user->getFirstName());
        $this->assertEquals(self::$userLastName, $user->getLastName());
        $this->assertEquals(self::$userEmail, $user->getEmail());
        $this->assertEquals(self::$userRoles, $user->getRoles());
        $this->assertNotEquals($newPasswordHash, $user->getPassword());
        $this->assertEquals(self::$userIsActive, $user->getIsActive());
    }
}
