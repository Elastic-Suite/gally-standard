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

namespace Gally\Security\Tests\Api\Rest;

use Gally\Test\AbstractTestCase;
use Gally\User\Constant\Role;
use Gally\User\Service\UserManager;
use Gally\User\Tests\LoginTrait;

class AuthenticationTest extends AbstractTestCase
{
    use LoginTrait;

    public function testLoginRest(): void
    {
        $this->loadFixture([]);

        $client = self::createClient();
        $catalog = ['code' => 'login_rest_catalog', 'name' => 'Login Rest catalog'];

        // Test before login.
        $client->request('GET', $this->getRoute('catalogs'));
        $this->assertResponseStatusCodeSame(200);
        $client->request('POST', $this->getRoute('catalogs'), ['json' => $catalog]);
        $this->assertResponseStatusCodeSame(401);

        // Log contributor.
        $token = $this->loginRest($client, $this->getUser(Role::ROLE_CONTRIBUTOR));
        $this->assertResponseIsSuccessful();
        $this->assertNotEmpty($token);

        // Test not authorized.
        $client->request('GET', $this->getRoute('catalogs'));
        $this->assertResponseStatusCodeSame(200);
        $client->request('POST', $this->getRoute('catalogs'), ['auth_bearer' => $token, 'json' => $catalog]);
        $this->assertResponseStatusCodeSame(403);

        // Log admin.
        $token = $this->loginRest($client, $this->getUser(Role::ROLE_ADMIN));
        $this->assertResponseIsSuccessful();
        $this->assertNotEmpty($token);

        // Test authorized.
        $client->request('GET', $this->getRoute('catalogs'));
        $this->assertResponseStatusCodeSame(200);
        $client->request('POST', $this->getRoute('catalogs'), ['auth_bearer' => $token, 'json' => $catalog]);
        $this->assertResponseStatusCodeSame(201);

        // Test that  "admin not active" user cannot log in and access to an API with a valid token (valid token generated before the user become inactive).
        $userManager = static::getContainer()->get(UserManager::class);
        $userManager->update('admin_not_active@test.com', null, null, null, null, null, true);
        $tokenAdminNotActive = $this->loginRest($client, $this->getUserByLoginData('admin_not_active@test.com', 'apassword'));
        $this->assertResponseIsSuccessful();
        $this->assertNotEmpty($token);

        $client->request('GET', $this->getRoute('catalogs'), ['auth_bearer' => $token]);
        $this->assertResponseStatusCodeSame(200);

        $this->logout();

        $userManager->update('admin_not_active@test.com', null, null, null, null, null, false);
        $token = $this->loginRest($client, $this->getUserByLoginData('admin_not_active@test.com', 'apassword'));
        $this->assertResponseStatusCodeSame(401);
        $this->assertEmpty($token);

        $client->request('GET', $this->getRoute('catalogs'), ['auth_bearer' => $tokenAdminNotActive]);
        $this->assertResponseStatusCodeSame(401);
    }
}
