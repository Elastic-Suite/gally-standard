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

namespace Gally\Job\Tests\Api\GraphQl\Job\Source;

use Gally\Job\Entity\Job;
use Gally\Job\Service\JobManager;
use Gally\Test\AbstractTestCase;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestGraphQlToTest;
use Gally\User\Constant\Role;
use Gally\User\Entity\User;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ProfileOptionTest extends AbstractTestCase
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
    public function testGetCollection(?User $user, array $expectedData, int $responseCode, ?string $jobType = null): void
    {
        $parameters = $jobType ? \sprintf('(jobType: "%s") ', $jobType) : '';
        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                      jobProfileOptions $parameters{
                        id
                        value
                        label
                      }
                    }
                GQL,
                $user,
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use ($expectedData, $responseCode) {
                    if (200 === $responseCode) {
                        $this->assertJsonContains(['data' => ['jobProfileOptions' => $expectedData]]);
                        $jobProfileOptions = $response->toArray()['data']['jobProfileOptions'];
                        foreach ($jobProfileOptions as $jobProfileOption) {
                            $this->assertArrayHasKey('label', $jobProfileOption);
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
        /** @var JobManager $jobManager */
        $jobManager = static::getContainer()->get(JobManager::class);
        $profileOptions = $jobManager->getProfileOptions();

        return [
            [null, ['error' => 'Access Denied.'], 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), $profileOptions, 200],
            [$this->getUser(Role::ROLE_ADMIN), $profileOptions, 200],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), $jobManager->getProfileOptions(Job::TYPE_IMPORT), 200, Job::TYPE_IMPORT],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), $jobManager->getProfileOptions(Job::TYPE_EXPORT), 200, Job::TYPE_EXPORT],
        ];
    }
}
