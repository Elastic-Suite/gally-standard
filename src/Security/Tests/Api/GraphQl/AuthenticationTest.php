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

namespace Gally\Security\Tests\Api\GraphQl;

use Gally\Test\AbstractTestCase;
use Gally\User\Constant\Role;
use Gally\User\Entity\User;
use Gally\User\Service\UserManager;
use Gally\User\Tests\LoginTrait;

class AuthenticationTest extends AbstractTestCase
{
    use LoginTrait;

    public function testLogin(): void
    {
        $client = self::createClient();
        $listQuery = [
            'operationName' => null,
            'variables' => [],
            'query' => <<<GQL
                { catalogs { edges { node { id } } } }
            GQL,
        ];
        $createQuery = [
            'operationName' => null,
            'variables' => [],
            'query' => <<<GQL
                mutation { createCatalog (input: { code: "test", name: "Test" }) { catalog { id } } }
            GQL,
        ];

        // Test before login
        $client->request('POST', $this->getRoute('graphql'), ['json' => $listQuery]);
        $this->assertJsonContains(['data' => []]);
        $response = $client->request('POST', $this->getRoute('graphql'), ['json' => $createQuery]);
        $this->assertEquals('Access Denied.', $response->toArray()['errors'][0]['message']);

        // Log contributor
        $token = $this->loginGraphQl($client, $this->getUser(Role::ROLE_CONTRIBUTOR));
        $this->assertResponseIsSuccessful();
        $this->assertNotEmpty($token);

        // Test not authorized.
        $client->request('POST', $this->getRoute('graphql'), ['json' => $listQuery]);
        $this->assertJsonContains(['data' => []]);
        $client->request('POST', $this->getRoute('graphql'), ['auth_bearer' => $token, 'json' => $createQuery]);
        $this->assertEquals('Access Denied.', $response->toArray()['errors'][0]['message']);

        // Log admin
        $token = $this->loginGraphQl($client, $this->getUser(Role::ROLE_ADMIN));
        $this->assertResponseIsSuccessful();
        $this->assertNotEmpty($token);

        // Test authorized.
        $client->request('POST', $this->getRoute('graphql'), ['json' => $listQuery]);
        $this->assertJsonContains(['data' => []]);
        $client->request('POST', $this->getRoute('graphql'), ['auth_bearer' => $token, 'json' => $createQuery]);
        $this->assertJsonContains(['data' => []]);

        // Test that  "admin not active" user cannot log in and access to an API with a valid token (valid token generated before the user become inactive).
        $userManager = static::getContainer()->get(UserManager::class);
        $userManager->update('admin_not_active@test.com', null, null, null, null, null, true);
        $tokenAdminNotActive = $this->loginGraphQl($client, $this->getUserByLoginData('admin_not_active@test.com', 'apassword'));
        $this->assertResponseIsSuccessful();
        $this->assertNotEmpty($token);

        $client->request('POST', $this->getRoute('graphql'), ['auth_bearer' => $token, 'json' => $listQuery]);
        $this->assertResponseStatusCodeSame(200);

        $this->logout();

        $userManager->update('admin_not_active@test.com', null, null, null, null, null, false);
        $token = $this->loginGraphQl($client, $this->getUserByLoginData('admin_not_active@test.com', 'apassword'));
        $this->assertJsonContains([
            'data' => [
                'tokenAuthentication' => [
                    'authentication' => [
                        'code' => 401,
                        'message' => 'Your account is inactive.',
                    ],
                ],
            ],
        ]);
        $this->assertEmpty($token);

        $client->request('POST', $this->getRoute('graphql'), ['auth_bearer' => $tokenAdminNotActive, 'json' => $listQuery]);
        $this->assertEquals('Access Denied.', $response->toArray()['errors'][0]['message']);
    }

    public function testLoginInvalidCredentials(): void
    {
        $client = self::createClient();
        $user = new User();
        $user->setEmail('fake@test.com')
            ->setPassword('fakepassword');
        $this->loginGraphQl($client, $user);

        $this->assertJsonContains([
            'data' => [
                'tokenAuthentication' => [
                    'authentication' => [
                        'code' => 401,
                        'message' => 'Invalid credentials.',
                    ],
                ],
            ],
        ]);
    }
}
