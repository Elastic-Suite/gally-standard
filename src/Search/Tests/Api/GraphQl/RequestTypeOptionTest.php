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

namespace Gally\Search\Tests\Api\GraphQl\Source;

use Gally\Test\AbstractTestCase;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestGraphQlToTest;
use Gally\User\Constant\Role;
use Gally\User\Entity\User;
use Symfony\Contracts\HttpClient\ResponseInterface;

class RequestTypeOptionTest extends AbstractTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        // Allows to  load user fixtures.
        self::loadFixture([]);
    }

    /**
     * @dataProvider getCollectionDataProvider
     */
    public function testGetCollection(?User $user, array $expectedData, int $responseCode): void
    {
        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                      requestTypeOptions { value label  }
                    }
                GQL,
                $user,
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use ($expectedData, $responseCode) {
                    if (200 === $responseCode) {
                        $this->assertJsonContains(['data' => ['requestTypeOptions' => $expectedData]]);
                        $boostRequestTypeOptions = $response->toArray()['data']['requestTypeOptions'];
                        foreach ($boostRequestTypeOptions as $boostRequestTypeOption) {
                            $this->assertArrayHasKey('label', $boostRequestTypeOption);
                        }
                    } else {
                        $this->assertGraphQlError($expectedData['error']);
                    }
                }
            )
        );
    }

    public function getCollectionDataProvider(): array
    {
        $requestTypeOptions = [
            [
                'value' => 'product_catalog',
                'label' => 'product_catalog',
            ],
            [
                'value' => 'product_search',
                'label' => 'product_search',
            ],
        ];

        return [
            [null, ['error' => 'Access Denied.'], 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), $requestTypeOptions, 200],
            [$this->getUser(Role::ROLE_ADMIN), $requestTypeOptions, 200],
        ];
    }
}
