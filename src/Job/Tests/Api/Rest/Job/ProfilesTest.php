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

namespace Gally\Job\Tests\Api\Rest\Job;

use Gally\Job\Tests\Api\GraphQl\Job\ProfilesTest as GraphQlProfilesTest;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestToTest;
use Gally\User\Constant\Role;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ProfilesTest extends GraphQlProfilesTest
{
    protected function getApiPath(): string
    {
        return 'job_profiles';
    }

    public function testSecurity(): void
    {
        $this->validateApiCall(
            new RequestToTest('GET', $this->getApiPath(), null),
            new ExpectedResponse(401)
        );
    }

    public function testGetCollection(): void
    {
        $request = new RequestToTest('GET', $this->getApiPath(), $this->getUser(Role::ROLE_CONTRIBUTOR));
        $expectedResponse = new ExpectedResponse(
            200,
            function (ResponseInterface $response) {
                $this->assertJsonContains(
                    [
                        '@context' => $this->getRoute('contexts/JobProfiles'),
                        '@id' => $this->getRoute('job_profiles'),
                        '@type' => 'JobProfiles',
                        'id' => 'job_profiles',
                        'profiles' => [],
                    ],
                );

                $this->checkResponseData($response->toArray());
            }
        );

        $this->validateApiCall($request, $expectedResponse);
    }
}
