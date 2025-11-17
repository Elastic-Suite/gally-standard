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

namespace Gally\Configuration\Tests\Api\Rest\Source;

use Gally\Configuration\Tests\Api\GraphQl\Source\LocaleGroupOptionTest as GraphQlLocaleGroupOptionTest;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestToTest;
use Gally\User\Constant\Role;
use Symfony\Contracts\HttpClient\ResponseInterface;

class LocaleGroupOptionTest extends GraphQlLocaleGroupOptionTest
{
    /**
     * @dataProvider getCollectionDataProvider
     */
    public function testGetCollection(array $expectedData): void
    {
        $this->validateApiCall(
            new RequestToTest('GET', 'configuration_locale_group_options', $this->getUser(Role::ROLE_CONTRIBUTOR)),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use ($expectedData) {
                    $this->assertJsonContains(['hydra:member' => $expectedData]);
                }
            )
        );
    }
}
