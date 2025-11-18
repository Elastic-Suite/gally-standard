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

namespace Gally\Locale\Tests\Api\Rest\Source;

use Gally\Locale\Tests\Api\GraphQl\Source\LanguageOptionTest as GraphQlLanguageOptionTest;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestToTest;
use Gally\User\Entity\User;
use Symfony\Contracts\HttpClient\ResponseInterface;

class LanguageOptionTest extends GraphQlLanguageOptionTest
{
    /**
     * @dataProvider getCollectionDataProvider
     */
    public function testGetCollection(?User $user, array $expectedData, int $responseCode = 200, ?string $expectedMessage = null): void
    {
        $this->validateApiCall(
            new RequestToTest('GET', 'language_options', $user),
            new ExpectedResponse(
                $responseCode,
                function (ResponseInterface $response) use ($expectedData) {
                    if ($response->getStatusCode() < 400) {
                        $responseData = $response->toArray();
                        $this->assertJsonContains(['hydra:member' => $expectedData]);
                        $this->assertCount(self::USED_LANGUAGE_COUNT, $responseData['hydra:member'][0]['options']);
                        $this->assertCount(self::UNUSED_LANGUAGE_COUNT, $responseData['hydra:member'][1]['options']);
                    } else {
                        $this->assertJsonContains(['@context' => $this->getRoute('contexts/LanguageOption'), '@type' => 'hydra:Collection']);
                    }
                }
            )
        );
    }
}
