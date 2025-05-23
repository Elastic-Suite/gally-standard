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
    private static string $userEmail = 'user@example.com';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::loadFixture([]);
    }

    public function testCreate(): void
    {
        $userManager = static::getContainer()->get(UserManager::class);
        $userRepository = static::getContainer()->get(UserRepository::class);
        $roles = [Role::ROLE_CONTRIBUTOR];
        $password = 'Gally123!';
        $userManager->create(self::$userEmail, $roles, $password);
        $user = $userRepository->findOneBy(['email' => self::$userEmail]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(self::$userEmail, $user->getEmail());
        $this->assertEquals($roles, $user->getRoles());
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
        $userManager->update('fake_' . self::$userEmail, self::$userEmail, null, null);
    }

    /**
     * @depends testCreate
     */
    public function testUpdate(): void
    {
        $userManager = static::getContainer()->get(UserManager::class);
        $userRepository = static::getContainer()->get(UserRepository::class);
        $this->assertTrue(true);
        $roles = [Role::ROLE_CONTRIBUTOR];
        $password = 'Gally123!';
        $newEmail = 'updated_user@example.com';
        $newRoles = [Role::ROLE_ADMIN, Role::ROLE_CONTRIBUTOR];
        $newPassword = 'NewGally123!';

        $user = $userRepository->findOneBy(['email' => self::$userEmail]);
        $user = clone $user;
        $this->assertInstanceOf(User::class, $user);

        $userManager->update(self::$userEmail, null, null, $newPassword);

        // Change password.
        $userUpdated = $userRepository->findOneBy(['email' => self::$userEmail]);
        $this->assertInstanceOf(User::class, $userUpdated);
        $newPasswordHash = $userUpdated->getPassword();
        $this->assertEquals($user->getEmail(), $userUpdated->getEmail());
        $this->assertEquals($user->getRoles(), $userUpdated->getRoles());
        $this->assertNotEquals($user->getPassword(), $newPasswordHash);

        // Change roles.
        $userManager->update(self::$userEmail, null, $newRoles, null);
        $userUpdated = $userRepository->findOneBy(['email' => self::$userEmail]);
        $this->assertInstanceOf(User::class, $userUpdated);
        $this->assertEquals($user->getEmail(), $userUpdated->getEmail());
        $this->assertEquals($newRoles, $userUpdated->getRoles());
        $this->assertEquals($userUpdated->getPassword(), $newPasswordHash);

        // Change email.
        $userManager->update(self::$userEmail, $newEmail, null, null);
        $this->assertInstanceOf(User::class, $userUpdated);
        $this->assertEquals($newEmail, $userUpdated->getEmail());
        $this->assertEquals($newRoles, $userUpdated->getRoles());
        $this->assertEquals($userUpdated->getPassword(), $newPasswordHash);

        // Change all.
        $userManager->update($newEmail, self::$userEmail, $roles, $password);
        $this->assertInstanceOf(User::class, $userUpdated);
        $this->assertEquals(self::$userEmail, $userUpdated->getEmail());
        $this->assertEquals($roles, $userUpdated->getRoles());
        $this->assertNotEquals($userUpdated->getPassword(), $newPasswordHash);
    }
}
