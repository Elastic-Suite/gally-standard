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

namespace Gally\Job\Tests\Api\GraphQl\Job;

use Gally\Job\Service\JobManager;
use Gally\Test\AbstractTestCase;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestGraphQlToTest;
use Gally\User\Constant\Role;
use Gally\User\Entity\User;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ProfilesTest extends AbstractTestCase
{
    public function testSecurity(): void
    {
        $this->getProfiles(null, 'Access Denied.');
    }

    public function testGetCollection(): void
    {
        $this->getProfiles($this->getUser(Role::ROLE_CONTRIBUTOR));
    }

    public function getProfiles(?User $user, ?string $expectedError = null): void
    {
        $request = new RequestGraphQlToTest(
            <<<GQL
                    query {
                        getJobProfiles
                        {
                            id
                            profiles
                        }
                    }
                GQL,
            $user,
        );

        $expectedResponse = new ExpectedResponse(
            200,
            function (ResponseInterface $response) use ($expectedError) {
                if (null !== $expectedError) {
                    $this->assertGraphQlError($expectedError);
                } else {
                    $this->assertJsonContains([
                        'data' => [
                            'getJobProfiles' => [
                                'id' => $this->getRoute('job_profiles'),
                                'profiles' => [],
                            ],
                        ],
                    ]);

                    $this->checkResponseData($response->toArray()['data']['getJobProfiles']);
                }
            }
        );

        $this->validateApiCall($request, $expectedResponse);
    }

    protected function checkResponseData(array $responseArray): void
    {
        $jobManager = static::getContainer()->get(JobManager::class);
        $this->assertEquals($jobManager->getProfiles(), $responseArray['profiles']);
    }
}
