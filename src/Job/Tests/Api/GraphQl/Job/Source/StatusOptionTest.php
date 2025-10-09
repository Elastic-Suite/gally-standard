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
use Gally\Test\AbstractTestCase;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestGraphQlToTest;
use Gally\User\Constant\Role;
use Gally\User\Entity\User;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class StatusOptionTest extends AbstractTestCase
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
                      jobStatusOptions {
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
                        $this->assertJsonContains(['data' => ['jobStatusOptions' => $expectedData]]);
                        $jobStatusOptions = $response->toArray()['data']['jobStatusOptions'];
                        foreach ($jobStatusOptions as $jobStatusOption) {
                            $this->assertArrayHasKey('label', $jobStatusOption);
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
        $translator = static::getContainer()->get(TranslatorInterface::class);
        $statusOptions = array_map(
            fn ($status) => [...$status, 'label' => $translator->trans($status['label'], [], 'gally_job')],
            Job::STATUS_OPTIONS
        );

        return [
            [null, ['error' => 'Access Denied.'], 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), $statusOptions, 200],
            [$this->getUser(Role::ROLE_ADMIN), $statusOptions, 200],
        ];
    }
}
