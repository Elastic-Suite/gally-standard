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
    private static UserManager $userManager;
    private static UserRepository $userRepository;
    private static string $userFirstName = 'John';
    private static string $userLastName = 'Doe';
    private static string $userEmail = 'user@example.com';
    private static array $userRoles = [Role::ROLE_CONTRIBUTOR];
    private static string $userPassword = 'Gally123';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::loadFixture([]);
        self::$userManager = static::getContainer()->get(UserManager::class);
        self::$userRepository = static::getContainer()->get(UserRepository::class);
    }

    public function testCreate(): void
    {
        self::$userManager->create(self::$userFirstName, self::$userLastName, self::$userEmail, self::$userRoles, self::$userPassword);
        $user = self::$userRepository->findOneBy(['email' => self::$userEmail]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(self::$userEmail, $user->getEmail());
        $this->assertEquals(self::$userRoles, $user->getRoles());
    }

    /**
     * @depends testCreate
     */
    public function testisUserExists()
    {
        $this->assertTrue(self::$userManager->isUserExists(self::$userEmail));
        $this->assertFalse(self::$userManager->isUserExists('fake_' . self::$userEmail));
    }

    public function testGetRoles()
    {
        $this->assertEquals(Role::ROLES, self::$userManager->getRoles());
    }

    public function testGetFakeRoles()
    {
        $fakeRole = 'ROLE_FAKE';
        $this->assertEquals([$fakeRole], self::$userManager->getFakeRoles([$fakeRole, Role::ROLE_ADMIN, Role::ROLE_CONTRIBUTOR]));
    }

    /**
     * @depends testCreate
     */
    public function testFailureUpdate(): void
    {
        // User not exists.
        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage(\sprintf("The user with the email 'fake_%s' was not found", self::$userEmail));
        self::$userManager->update('fake_' . self::$userEmail, null, null, self::$userEmail, null, null);
    }

    /**
     * @depends testCreate
     */
    public function testUpdate(): void
    {
        $password = 'Gally123!';
        $newFirstName = 'Tony';
        $newLastName = 'Dark';
        $newEmail = 'updated_user@example.com';
        $newRoles = [Role::ROLE_ADMIN, Role::ROLE_CONTRIBUTOR];
        $newPassword = 'NewGally123!';

        $user = self::$userRepository->findOneBy(['email' => self::$userEmail]);
        $this->assertInstanceOf(User::class, $user);
        $passwordHash = $user->getPassword();

        // Change password.
        self::$userManager->update(self::$userEmail, null, null, null, null, $newPassword);
        $newPasswordHash = $user->getPassword();
        $this->assertEquals(self::$userFirstName, $user->getFirstName());
        $this->assertEquals(self::$userLastName, $user->getLastName());
        $this->assertEquals(self::$userEmail, $user->getEmail());
        $this->assertEquals(self::$userRoles, $user->getRoles());
        $this->assertNotEquals($passwordHash, $newPasswordHash);

        // Change first name.
        self::$userManager->update(self::$userEmail, $newFirstName, null, null, null, null);
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($newFirstName, $user->getFirstName());
        $this->assertEquals(self::$userLastName, $user->getLastName());
        $this->assertEquals(self::$userEmail, $user->getEmail());
        $this->assertEquals(self::$userRoles, $user->getRoles());
        $this->assertEquals($newPasswordHash, $user->getPassword());

        // Change last name.
        self::$userManager->update(self::$userEmail, null, $newLastName, null, null, null);
        $this->assertEquals($newFirstName, $user->getFirstName());
        $this->assertEquals($newLastName, $user->getLastName());
        $this->assertEquals(self::$userEmail, $user->getEmail());
        $this->assertEquals(self::$userRoles, $user->getRoles());
        $this->assertEquals($newPasswordHash, $user->getPassword());

        // Change roles.
        self::$userManager->update(self::$userEmail, null, null, null, $newRoles, null);
        $this->assertEquals($newFirstName, $user->getFirstName());
        $this->assertEquals($newLastName, $user->getLastName());
        $this->assertEquals(self::$userEmail, $user->getEmail());
        $this->assertEquals($newRoles, $user->getRoles());
        $this->assertEquals($newPasswordHash, $user->getPassword());

        // Change email.
        self::$userManager->update(self::$userEmail, null, null, $newEmail, null, null);
        $this->assertEquals($newFirstName, $user->getFirstName());
        $this->assertEquals($newLastName, $user->getLastName());
        $this->assertEquals($newEmail, $user->getEmail());
        $this->assertEquals($newRoles, $user->getRoles());
        $this->assertEquals($newPasswordHash, $user->getPassword());

        // Change all.
        self::$userManager->update($newEmail, self::$userFirstName, self::$userLastName, self::$userEmail, self::$userRoles, $password);
        $this->assertEquals(self::$userFirstName, $user->getFirstName());
        $this->assertEquals(self::$userLastName, $user->getLastName());
        $this->assertEquals(self::$userEmail, $user->getEmail());
        $this->assertEquals(self::$userRoles, $user->getRoles());
        $this->assertNotEquals($newPasswordHash, $user->getPassword());
    }
}
