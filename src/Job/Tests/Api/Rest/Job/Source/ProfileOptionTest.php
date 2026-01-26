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

namespace Gally\Job\Tests\Api\Rest\Job\Source;

use Gally\Job\Tests\Api\GraphQl\Job\Source\ProfileOptionTest as GraphQlProfileOptionTest;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestToTest;
use Gally\User\Entity\User;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ProfileOptionTest extends GraphQlProfileOptionTest
{
    /**
     * @dataProvider getCollectionDataProvider
     */
    public function testGetCollection(?User $user, array $expectedData, int $responseCode, ?string $jobType = null): void
    {
        $this->validateApiCall(
            new RequestToTest('GET', 'job_profile_options' . ($jobType ? "?jobType={$jobType}" : ''), $user),
            new ExpectedResponse(
                $responseCode,
                function (ResponseInterface $response) use ($expectedData, $responseCode) {
                    if (200 === $responseCode) {
                        $this->assertJsonContains(['hydra:member' => $expectedData]);
                    }
                }
            )
        );
    }
}
