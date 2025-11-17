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

namespace Gally\Locale\Tests\Api\GraphQl\Source;

use Gally\Test\AbstractTestCase;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestGraphQlToTest;
use Gally\User\Constant\Role;
use Gally\User\Entity\User;
use Symfony\Contracts\HttpClient\ResponseInterface;

class LocaleGroupOptionTest extends AbstractTestCase
{
    protected const USED_LOCALE_COUNT = 2;
    protected const UNUSED_LOCALE_COUNT = 497;

    /**
     * @dataProvider getCollectionDataProvider
     */
    public function testGetCollection(?User $user, array $expectedData, int $responseCode = 200, ?string $expectedMessage = null): void
    {
        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                      localeGroupOptions {
                        id
                        value
                        label
                        options
                      }
                    }
                GQL,
                $user
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use ($expectedData, $responseCode, $expectedMessage) {
                    if ($responseCode < 400) {
                        $responseData = $response->toArray();
                        $this->assertJsonContains(['data' => ['localeGroupOptions' => $expectedData]]);
                        $this->assertCount(self::USED_LOCALE_COUNT, $responseData['data']['localeGroupOptions'][0]['options']);
                        $this->assertCount(self::UNUSED_LOCALE_COUNT, $responseData['data']['localeGroupOptions'][1]['options']);
                    } else {
                        $this->assertJsonContains(['errors' => [['message' => $expectedMessage]]]);
                    }
                }
            )
        );
    }

    public function getCollectionDataProvider(): array
    {
        return [
            [
                null, [], 401, 'Access Denied.',
            ],
            [
                $this->getUser(Role::ROLE_CONTRIBUTOR),
                [
                    [
                        'id' => 'used_locale',
                        'value' => 'used_locale',
                        'label' => 'Locale(s) used in catalog(s)',
                        'options' => [
                            ['value' => 'en_US', 'label' => 'English (United States)'],
                            ['value' => 'fr_FR', 'label' => 'French (France)'],
                        ],
                    ],
                    [
                        'id' => 'unused_locale',
                        'value' => 'unused_locale',
                        'label' => 'Unused Locales',
                        'options' => [
                            ['value' => 'af_NA', 'label' => 'Afrikaans (Namibia)'],
                            ['value' => 'af_ZA', 'label' => 'Afrikaans (South Africa)'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
