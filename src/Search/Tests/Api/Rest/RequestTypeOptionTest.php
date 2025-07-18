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

namespace Gally\Boost\Tests\Api\Rest\Source;

use Gally\Boost\Tests\Api\GraphQl\Source\RequestTypeOptionTest as GraphQlRequestTypeOptionTest;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestToTest;
use Gally\User\Entity\User;
use Symfony\Contracts\HttpClient\ResponseInterface;

class RequestTypeOptionTest extends GraphQlRequestTypeOptionTest
{
    /**
     * @dataProvider getCollectionDataProvider
     */
    public function testGetCollection(?User $user, array $expectedData, int $responseCode): void
    {
        $this->validateApiCall(
            new RequestToTest('GET', 'request_type_options', $user),
            new ExpectedResponse(
                $responseCode,
                function (ResponseInterface $response) use ($expectedData, $responseCode) {
                    if (200 === $responseCode) {
                        $this->assertJsonContains(['hydra:member' => $expectedData]);
                    }
                }
            )
        );
    }
}
