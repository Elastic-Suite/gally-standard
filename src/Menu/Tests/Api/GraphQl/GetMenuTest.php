<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @package   Gally
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Gally\Menu\Tests\Api\GraphQl;

use Gally\Locale\EventSubscriber\LocaleSubscriber;
use Gally\Menu\Tests\Api\AbstractMenuTest;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestGraphQlToTest;
use Gally\User\Model\User;
use Symfony\Contracts\HttpClient\ResponseInterface;

class GetMenuTest extends AbstractMenuTest
{
    public function testSecurity(): void
    {
        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                      getMenu {
                         hierarchy
                      }
                    }
                GQL,
                null
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) {
                    $this->assertJsonContains([
                        'errors' => [
                            [
                                'debugMessage' => 'Access Denied.',
                            ],
                        ],
                    ]);
                }
            )
        );
    }

    /**
     * @dataProvider menuDataProvider
     */
    public function testGetMenu(string $local, array $expectedResponse, User $user): void
    {
        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                      getMenu {
                         hierarchy
                      }
                    }
                GQL,
                $user,
                [LocaleSubscriber::GALLY_LANGUAGE_HEADER => $local]
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use ($expectedResponse) {
                    $this->assertJsonContains(['data' => ['getMenu' => $expectedResponse]]);
                }
            )
        );
    }
}
