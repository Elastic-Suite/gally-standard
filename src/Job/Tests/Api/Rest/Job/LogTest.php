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

use Gally\Job\Entity\Job;
use Gally\Test\AbstractEntityTestWithUpdate;
use Gally\User\Constant\Role;

class LogTest extends AbstractEntityTestWithUpdate
{
    protected static function getFixtureFiles(): array
    {
        return [
            __DIR__ . '/../../../fixtures/job_logs.yaml',
        ];
    }

    protected function getEntityClass(): string
    {
        return Job\Log::class;
    }

    public function createDataProvider(): iterable
    {
        $contributorUser = $this->getUser(Role::ROLE_CONTRIBUTOR);

        return [
            [null, [], 405],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), [], 405],
            [$this->getUser(Role::ROLE_ADMIN), [], 405],
        ];
    }

    public function getDataProvider(): iterable
    {
        $user = $this->getUser(Role::ROLE_CONTRIBUTOR);

        return [
            [null, 1, ['id' => 1, 'message' => 'Start dummy export'], 401],
            [
                $user,
                1,
                [
                    'id' => 1,
                    'loggedAt' => '2025-11-07T17:07:01+00:00',
                    'severity' => 'info',
                    'message' => 'Start dummy export',
                ],
                200,
            ],
            [
                $this->getUser(Role::ROLE_ADMIN),
                2,
                [
                    'id' => 2,
                    'loggedAt' => '2025-11-07T17:07:01+00:00',
                    'severity' => 'info',
                    'message' => 'Do nothing',
                ],
                200,
            ],
            [$user, 10, [], 404],
        ];
    }

    public function deleteDataProvider(): iterable
    {
        $user = $this->getUser(Role::ROLE_ADMIN);

        return [
            [null, 1, 405],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 1, 405],
            [$user, 1, 405],
            [$user, 10, 405],
        ];
    }

    public function getCollectionDataProvider(): iterable
    {
        return [
            [null, 4, 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 4, 200],
            [$this->getUser(Role::ROLE_ADMIN), 4, 200],
        ];
    }

    public function patchUpdateDataProvider(): iterable
    {
        return [
            [null, 1, ['type' => Job::TYPE_IMPORT], 405],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 1, ['type' => Job::TYPE_IMPORT], 405],
            [$this->getUser(Role::ROLE_ADMIN), 1, ['type' => Job::TYPE_IMPORT], 405],
        ];
    }

    public function putUpdateDataProvider(): iterable
    {
        return $this->patchUpdateDataProvider();
    }
}
