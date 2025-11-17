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

namespace Gally\Configuration\Tests\Api\GraphQl\Source;

use Gally\Test\AbstractTestCase;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestGraphQlToTest;
use Gally\User\Constant\Role;
use Symfony\Contracts\HttpClient\ResponseInterface;

class LanguageOptionTest extends AbstractTestCase
{
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
    public function testGetCollection(array $expectedData): void
    {
        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                      configurationLanguageOptions {
                        value
                        label
                        options
                      }
                    }
                GQL,
                $this->getUser(Role::ROLE_CONTRIBUTOR),
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use ($expectedData) {
                    $responseData = $response->toArray();
                    $this->assertSame($expectedData, $responseData['data']['configurationLanguageOptions']);
                }
            )
        );
    }

    public function getCollectionDataProvider(): array
    {
        return [
            [
                [
                    [
                        'value' => 'general',
                        'label' => 'General',
                        'options' => [
                            [
                                'value' => 'general',
                                'label' => 'All languages',
                            ],
                        ],
                    ],
                    [
                        'value' => 'used_language',
                        'label' => 'Language(s) used in catalog(s)',
                        'options' => [
                            [
                                'value' => 'en',
                                'label' => 'en',
                            ],
                            [
                                'value' => 'fr',
                                'label' => 'fr',
                            ],
                        ],
                    ],
                    [
                        'value' => 'unused_language',
                        'label' => 'Unused Languages',
                        'options' => [
                            ['value' => 'af', 'label' => 'af'],
                            ['value' => 'ak', 'label' => 'ak'],
                            ['value' => 'am', 'label' => 'am'],
                            ['value' => 'ar', 'label' => 'ar'],
                            ['value' => 'as', 'label' => 'as'],
                            ['value' => 'az', 'label' => 'az'],
                            ['value' => 'be', 'label' => 'be'],
                            ['value' => 'bg', 'label' => 'bg'],
                            ['value' => 'bm', 'label' => 'bm'],
                            ['value' => 'bn', 'label' => 'bn'],
                            ['value' => 'bo', 'label' => 'bo'],
                            ['value' => 'br', 'label' => 'br'],
                            ['value' => 'bs', 'label' => 'bs'],
                            ['value' => 'ca', 'label' => 'ca'],
                            ['value' => 'ce', 'label' => 'ce'],
                            ['value' => 'cs', 'label' => 'cs'],
                            ['value' => 'cv', 'label' => 'cv'],
                            ['value' => 'cy', 'label' => 'cy'],
                            ['value' => 'da', 'label' => 'da'],
                            ['value' => 'de', 'label' => 'de'],
                            ['value' => 'dz', 'label' => 'dz'],
                            ['value' => 'ee', 'label' => 'ee'],
                            ['value' => 'el', 'label' => 'el'],
                            ['value' => 'en', 'label' => 'en'],
                            ['value' => 'es', 'label' => 'es'],
                            ['value' => 'et', 'label' => 'et'],
                            ['value' => 'eu', 'label' => 'eu'],
                            ['value' => 'fa', 'label' => 'fa'],
                            ['value' => 'ff', 'label' => 'ff'],
                            ['value' => 'fi', 'label' => 'fi'],
                            ['value' => 'fo', 'label' => 'fo'],
                            ['value' => 'fr', 'label' => 'fr'],
                            ['value' => 'fy', 'label' => 'fy'],
                            ['value' => 'ga', 'label' => 'ga'],
                            ['value' => 'gd', 'label' => 'gd'],
                            ['value' => 'gl', 'label' => 'gl'],
                            ['value' => 'gu', 'label' => 'gu'],
                            ['value' => 'gv', 'label' => 'gv'],
                            ['value' => 'ha', 'label' => 'ha'],
                            ['value' => 'he', 'label' => 'he'],
                            ['value' => 'hi', 'label' => 'hi'],
                            ['value' => 'hr', 'label' => 'hr'],
                            ['value' => 'hu', 'label' => 'hu'],
                            ['value' => 'hy', 'label' => 'hy'],
                            ['value' => 'id', 'label' => 'id'],
                            ['value' => 'ie', 'label' => 'ie'],
                            ['value' => 'ig', 'label' => 'ig'],
                            ['value' => 'ii', 'label' => 'ii'],
                            ['value' => 'in', 'label' => 'in'],
                            ['value' => 'is', 'label' => 'is'],
                            ['value' => 'it', 'label' => 'it'],
                            ['value' => 'iw', 'label' => 'iw'],
                            ['value' => 'ja', 'label' => 'ja'],
                            ['value' => 'jv', 'label' => 'jv'],
                            ['value' => 'ka', 'label' => 'ka'],
                            ['value' => 'ki', 'label' => 'ki'],
                            ['value' => 'kk', 'label' => 'kk'],
                            ['value' => 'kl', 'label' => 'kl'],
                            ['value' => 'km', 'label' => 'km'],
                            ['value' => 'kn', 'label' => 'kn'],
                            ['value' => 'ko', 'label' => 'ko'],
                            ['value' => 'ks', 'label' => 'ks'],
                            ['value' => 'ku', 'label' => 'ku'],
                            ['value' => 'kw', 'label' => 'kw'],
                            ['value' => 'ky', 'label' => 'ky'],
                            ['value' => 'lb', 'label' => 'lb'],
                            ['value' => 'lg', 'label' => 'lg'],
                            ['value' => 'ln', 'label' => 'ln'],
                            ['value' => 'lo', 'label' => 'lo'],
                            ['value' => 'lt', 'label' => 'lt'],
                            ['value' => 'lu', 'label' => 'lu'],
                            ['value' => 'lv', 'label' => 'lv'],
                            ['value' => 'mg', 'label' => 'mg'],
                            ['value' => 'mi', 'label' => 'mi'],
                            ['value' => 'mk', 'label' => 'mk'],
                            ['value' => 'ml', 'label' => 'ml'],
                            ['value' => 'mn', 'label' => 'mn'],
                            ['value' => 'mr', 'label' => 'mr'],
                            ['value' => 'ms', 'label' => 'ms'],
                            ['value' => 'mt', 'label' => 'mt'],
                            ['value' => 'my', 'label' => 'my'],
                            ['value' => 'nb', 'label' => 'nb'],
                            ['value' => 'nd', 'label' => 'nd'],
                            ['value' => 'ne', 'label' => 'ne'],
                            ['value' => 'nl', 'label' => 'nl'],
                            ['value' => 'nn', 'label' => 'nn'],
                            ['value' => 'no', 'label' => 'no'],
                            ['value' => 'oc', 'label' => 'oc'],
                            ['value' => 'om', 'label' => 'om'],
                            ['value' => 'or', 'label' => 'or'],
                            ['value' => 'os', 'label' => 'os'],
                            ['value' => 'pa', 'label' => 'pa'],
                            ['value' => 'pl', 'label' => 'pl'],
                            ['value' => 'ps', 'label' => 'ps'],
                            ['value' => 'pt', 'label' => 'pt'],
                            ['value' => 'qu', 'label' => 'qu'],
                            ['value' => 'rm', 'label' => 'rm'],
                            ['value' => 'rn', 'label' => 'rn'],
                            ['value' => 'ro', 'label' => 'ro'],
                            ['value' => 'ru', 'label' => 'ru'],
                            ['value' => 'rw', 'label' => 'rw'],
                            ['value' => 'sa', 'label' => 'sa'],
                            ['value' => 'sc', 'label' => 'sc'],
                            ['value' => 'sd', 'label' => 'sd'],
                            ['value' => 'se', 'label' => 'se'],
                            ['value' => 'sg', 'label' => 'sg'],
                            ['value' => 'sh', 'label' => 'sh'],
                            ['value' => 'si', 'label' => 'si'],
                            ['value' => 'sk', 'label' => 'sk'],
                            ['value' => 'sl', 'label' => 'sl'],
                            ['value' => 'sn', 'label' => 'sn'],
                            ['value' => 'so', 'label' => 'so'],
                            ['value' => 'sq', 'label' => 'sq'],
                            ['value' => 'sr', 'label' => 'sr'],
                            ['value' => 'su', 'label' => 'su'],
                            ['value' => 'sv', 'label' => 'sv'],
                            ['value' => 'sw', 'label' => 'sw'],
                            ['value' => 'ta', 'label' => 'ta'],
                            ['value' => 'te', 'label' => 'te'],
                            ['value' => 'tg', 'label' => 'tg'],
                            ['value' => 'th', 'label' => 'th'],
                            ['value' => 'ti', 'label' => 'ti'],
                            ['value' => 'tk', 'label' => 'tk'],
                            ['value' => 'tl', 'label' => 'tl'],
                            ['value' => 'to', 'label' => 'to'],
                            ['value' => 'tr', 'label' => 'tr'],
                            ['value' => 'tt', 'label' => 'tt'],
                            ['value' => 'ug', 'label' => 'ug'],
                            ['value' => 'uk', 'label' => 'uk'],
                            ['value' => 'ur', 'label' => 'ur'],
                            ['value' => 'uz', 'label' => 'uz'],
                            ['value' => 'vi', 'label' => 'vi'],
                            ['value' => 'wo', 'label' => 'wo'],
                            ['value' => 'xh', 'label' => 'xh'],
                            ['value' => 'yi', 'label' => 'yi'],
                            ['value' => 'yo', 'label' => 'yo'],
                            ['value' => 'za', 'label' => 'za'],
                            ['value' => 'zh', 'label' => 'zh'],
                            ['value' => 'zu', 'label' => 'zu'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
