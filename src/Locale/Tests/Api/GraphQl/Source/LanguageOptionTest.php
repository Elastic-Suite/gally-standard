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

class LanguageOptionTest extends AbstractTestCase
{
    protected const USED_LANGUAGE_COUNT = 2;
    protected const UNUSED_LANGUAGE_COUNT = 142;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::loadFixture([
            __DIR__ . '/../../../fixtures/catalogs.yaml',
        ]);
    }

    /**
     * @dataProvider getCollectionDataProvider
     */
    public function testGetCollection(?User $user, array $expectedData, int $responseCode = 200, ?string $expectedMessage = null): void
    {
        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                      languageOptions {
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
                        $this->assertJsonContains(['data' => ['languageOptions' => $expectedData]]);
                        $this->assertCount(self::USED_LANGUAGE_COUNT, $responseData['data']['languageOptions'][0]['options']);
                        $this->assertCount(self::UNUSED_LANGUAGE_COUNT, $responseData['data']['languageOptions'][1]['options']);
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
                        'id' => 'used_language',
                        'value' => 'used_language',
                        'label' => 'Language(s) used in catalog(s)',
                        'options' => [
                            ['value' => 'en', 'label' => 'en'],
                            ['value' => 'fr', 'label' => 'fr'],
                        ],
                    ],
                    [
                        'id' => 'unused_language',
                        'value' => 'unused_language',
                        'label' => 'Unused Languages',
                        'options' => [
                            ['value' => 'af', 'label' => 'af'],
                            ['value' => 'ak', 'label' => 'ak'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
