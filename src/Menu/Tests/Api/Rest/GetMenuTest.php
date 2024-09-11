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

namespace Gally\Menu\Tests\Api\Rest;

use Gally\Locale\EventSubscriber\LocaleSubscriber;
use Gally\Menu\Tests\Api\AbstractMenuTestCase;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestToTest;
use Gally\User\Entity\User;
use Symfony\Contracts\HttpClient\ResponseInterface;

class GetMenuTest extends AbstractMenuTestCase
{
    public function testSecurity(): void
    {
        $this->validateApiCall(
            new RequestToTest('GET', 'menu', null),
            new ExpectedResponse(401)
        );
    }

    /**
     * @dataProvider menuDataProvider
     */
    public function testGetMenu(string $local, array $expectedResponse, User $user): void
    {
        $this->validateApiCall(
            new RequestToTest('GET', 'menu', $user, [], [LocaleSubscriber::GALLY_LANGUAGE_HEADER => $local]),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use ($expectedResponse) {
                    $this->assertJsonContains($expectedResponse);
                }
            )
        );
    }
}
