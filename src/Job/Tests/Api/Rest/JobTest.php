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

namespace Gally\Job\Tests\Api\Rest;

use Gally\Job\Entity\Job;
use Gally\Job\Tests\Job\DummyExport;
use Gally\Job\Tests\Job\DummyImport;
use Gally\Test\AbstractEntityTestWithUpdate;
use Gally\User\Constant\Role;

class JobTest extends AbstractEntityTestWithUpdate
{
    protected static function getFixtureFiles(): array
    {
        return [
            __DIR__ . '/../../fixtures/job_files.yaml',
        ];
    }

    protected function getEntityClass(): string
    {
        return Job::class;
    }

    public function createDataProvider(): iterable
    {
        $contributorUser = $this->getUser(Role::ROLE_CONTRIBUTOR);

        return [
            [
                null,
                [
                    'file' => $this->getUri('job_files', 1),
                    'type' => Job::TYPE_IMPORT,
                    'profile' => DummyImport::JOB_PROFILE,
                ],
                401,
            ],
            [
                $this->getUser(Role::ROLE_ADMIN),
                [
                    'file' => $this->getUri('job_files', 1),
                    'type' => Job::TYPE_IMPORT,
                    'profile' => DummyImport::JOB_PROFILE,
                ],
                201,
            ],
            [
                $contributorUser,
                [
                    'file' => $this->getUri('job_files', 2),
                    'type' => Job::TYPE_IMPORT,
                    'profile' => DummyImport::JOB_PROFILE,
                ],
                201,
            ],
            [
                $contributorUser,
                [
                    'type' => Job::TYPE_EXPORT,
                    'profile' => DummyExport::JOB_PROFILE,
                ],
                201,
            ],
            [
                $contributorUser,
                [
                    'type' => 'fake_type',
                    'profile' => DummyExport::JOB_PROFILE,
                ],
                422,
                'type: The value you selected is not a valid choice.',
            ],
            [
                $contributorUser,
                [
                    'type' => Job::TYPE_EXPORT,
                    'profile' => 'fake_profile',
                ],
                422,
                'profile: The profile "fake_profile" does not exist for jobs of type "export".',
            ],
            [
                $contributorUser,
                [
                    'type' => Job::TYPE_EXPORT,
                    'profile' => DummyImport::JOB_PROFILE,
                ],
                422,
                'profile: The profile "dummy_import" does not exist for jobs of type "export".',
            ],
            [
                $contributorUser,
                [
                    'file' => $this->getUri('job_files', 3),
                    'type' => Job::TYPE_EXPORT,
                    'profile' => DummyExport::JOB_PROFILE,
                ],
                422,
                'file: A file cannot be uploaded for "export" jobs.',
            ],
            [
                $contributorUser,
                [
                    'type' => Job::TYPE_IMPORT,
                    'profile' => DummyImport::JOB_PROFILE,
                ],
                422,
                'file: A file is required for "import" jobs.',
            ],
            [
                $contributorUser,
                [
                    'file' => $this->getUri('job_files', 1),
                    'type' => Job::TYPE_IMPORT,
                    'profile' => DummyImport::JOB_PROFILE,
                ],
                422,
                'file: The file uploaded is already used for another job.',
            ],
        ];
    }

    public function getDataProvider(): iterable
    {
        $user = $this->getUser(Role::ROLE_CONTRIBUTOR);

        return [
            [null, 1, ['id' => 1, 'type' => Job::TYPE_IMPORT], 401],
            [
                $user,
                1,
                [
                    'id' => 1,
                    'type' => Job::TYPE_IMPORT,
                    'profile' => DummyImport::JOB_PROFILE,
                    'status' => Job::STATUS_NEW,
                    'file' => $this->getUri('job_files', 1),
                    'logs' => [],
                ],
                200,
            ],
            [$this->getUser(Role::ROLE_ADMIN), 1, ['id' => 1, 'type' => Job::TYPE_IMPORT], 200],
            [$user, 10, [], 404],
        ];
    }

    public function deleteDataProvider(): iterable
    {
        $user = $this->getUser(Role::ROLE_ADMIN);

        return [
            [null, 1, 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 1, 403],
            [$user, 1, 204],
            [$user, 10, 404],
        ];
    }

    public function getCollectionDataProvider(): iterable
    {
        return [
            [null, 2, 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 2, 200],
            [$this->getUser(Role::ROLE_ADMIN), 2, 200],
        ];
    }

    public function patchUpdateDataProvider(): iterable
    {
        return [
            [null, 2, ['type' => Job::TYPE_IMPORT], 405],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 2, ['type' => Job::TYPE_IMPORT], 405],
            [$this->getUser(Role::ROLE_ADMIN), 2, ['type' => Job::TYPE_IMPORT], 405],
        ];
    }
}
