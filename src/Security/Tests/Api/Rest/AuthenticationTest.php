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
use Gally\User\Tests\LoginTrait;

class AuthenticationTest extends AbstractTestCase
{
    use LoginTrait;

    public function testLoginRest(): void
    {
        $this->loadFixture([]);

        $client = self::createClient();
        $catalog = ['code' => 'login_rest_catalog', 'name' => 'Login Rest catalog'];

        // Test before login
        $client->request('GET', $this->getRoute('catalogs'));
        $this->assertResponseStatusCodeSame(200);
        $client->request('POST', $this->getRoute('catalogs'), ['json' => $catalog]);
        $this->assertResponseStatusCodeSame(401);

        // Log contributor
        $token = $this->loginRest($client, $this->getUser(Role::ROLE_CONTRIBUTOR));
        $this->assertResponseIsSuccessful();
        $this->assertNotEmpty($token);

        // Test not authorized.
        $client->request('GET', $this->getRoute('catalogs'));
        $this->assertResponseStatusCodeSame(200);
        $client->request('POST', $this->getRoute('catalogs'), ['auth_bearer' => $token, 'json' => $catalog]);
        $this->assertResponseStatusCodeSame(403);

        // Log admin
        $token = $this->loginRest($client, $this->getUser(Role::ROLE_ADMIN));
        $this->assertResponseIsSuccessful();
        $this->assertNotEmpty($token);

        // Test authorized.
        $client->request('GET', $this->getRoute('catalogs'));
        $this->assertResponseStatusCodeSame(200);
        $client->request('POST', $this->getRoute('catalogs'), ['auth_bearer' => $token, 'json' => $catalog]);
        $this->assertResponseStatusCodeSame(201);

        $this->logout();
    }
}
