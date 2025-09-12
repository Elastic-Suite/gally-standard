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

class RequestTypeOptionTest extends AbstractTestCase
{
    /**
     * @dataProvider getCollectionDataProvider
     */
    public function testGetCollection(array $expectedData): void
    {
        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                      configurationRequestTypeOptions {
                        value
                        label
                      }
                    }
                GQL,
                $this->getUser(Role::ROLE_CONTRIBUTOR),
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use ($expectedData) {
                    $responseData = $response->toArray();
                    $this->assertSame($expectedData, $responseData['data']['configurationRequestTypeOptions']);
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
                        'label' => 'All request types',
                    ],
                    [
                        'value' => 'product_catalog',
                        'label' => 'product_catalog',
                    ],
                    [
                        'value' => 'product_search',
                        'label' => 'product_search',
                    ],
                    [
                        'value' => 'product_autocomplete',
                        'label' => 'product_autocomplete',
                    ],
                    [
                        'value' => 'product_category_count',
                        'label' => 'product_category_count',
                    ],
                    [
                        'value' => 'test_search_query',
                        'label' => 'test_search_query',
                    ],
                ],
            ],
        ];
    }
}
