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

namespace Gally\User\Tests;

use ApiPlatform\Symfony\Bundle\Test\Client;
use Gally\User\Constant\Role;
use Gally\User\Entity\User;

/**
 * Trait LoginTrait.
 */
trait LoginTrait
{
    private static array $tokens = [];

    public static function getUserFixtures(): array
    {
        return [__DIR__ . '/fixtures/test_user.yaml'];
    }

    public function getUser(string $role): User
    {
        $user = new User();
        $user->setRoles([$role])
            ->setEmail(match ($role) {
                Role::ROLE_ADMIN => 'admin@test.com',
                default => 'contributor@test.com',
            })
            ->setPassword('apassword');

        return $user;
    }

    public function getUserByLoginData(string $email, string $password): User
    {
        $user = new User();
        $user->setEmail($email)
            ->setPassword($password);

        return $user;
    }

    public function loginRest(Client $client, User $user): string
    {
        $routePrefix = static::getContainer()->getParameter('route_prefix');
        $email = $user->getEmail();
        if (!isset(self::$tokens[$email])) {
            $response = $client->request('POST', $routePrefix . '/authentication_token', [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => [
                    'email' => $user->getEmail(),
                    'password' => $user->getPassword(),
                ],
            ]);

            if (200 !== $response->getStatusCode()) {
                return '';
            }

            self::$tokens[$email] = $response->toArray()['token'];
        }

        return self::$tokens[$email];
    }

    public function loginGraphQl(Client $client, User $user): string
    {
        $email = $user->getEmail();
        $routePrefix = static::getContainer()->getParameter('route_prefix');
        if (!isset(self::$tokens[$email])) {
            $response = $client->request(
                'POST',
                $routePrefix . '/graphql',
                [
                    'json' => [
                        'operationName' => null,
                        'query' => <<<GQL
                            mutation {
                              tokenAuthentication(input: {email: "{$user->getEmail()}", password: "{$user->getPassword()}"}) {
                                authentication { token code message }
                              }
                            }
                        GQL,
                        'variables' => [],
                    ],
                ]
            );

            if (!($response->toArray()['data']['tokenAuthentication']['authentication']['token'] ?? null)) {
                return '';
            }

            self::$tokens[$email] = $response->toArray()['data']['tokenAuthentication']['authentication']['token'];
        }

        return self::$tokens[$email];
    }

    public function logout(): void
    {
        self::$tokens = [];
    }
}
