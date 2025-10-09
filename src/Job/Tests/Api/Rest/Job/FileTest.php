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

class FileTest extends AbstractEntityTestWithUpdate
{
    protected static function getFixtureFiles(): array
    {
        return [];
    }

    protected function getEntityClass(): string
    {
        return Job\File::class;
    }

    public function createDataProvider(): iterable
    {
        $contributorUser = $this->getUser(Role::ROLE_CONTRIBUTOR);
        $importFile1 = static::uploadFile(__DIR__ . '/../../../fixtures/jobs/files/test_unit_dummy_import.csv', 'test_unit_dummy_import_1.csv');
        $importFile2 = static::uploadFile(__DIR__ . '/../../../fixtures/jobs/files/test_unit_dummy_import.csv', 'test_unit_dummy_import_2.csv');
        $csvFile = static::uploadFile(__DIR__ . '/../../../fixtures/jobs/files/corner-expand.svg');

        return [
            [
                null,
                [],
                401,
                null,
                null,
                ['file' => $importFile1],
            ],
            [
                $contributorUser,
                [],
                201,
                null,
                null,
                ['file' => $importFile1],
            ],
            [
                $this->getUser(Role::ROLE_ADMIN),
                [],
                201,
                null,
                null,
                ['file' => $importFile2],
            ],
            [
                $this->getUser(Role::ROLE_ADMIN),
                [],
                422,
                'file: The uploaded file is not a csv.',
                null,
                ['file' => $csvFile],
            ],
            [
                $this->getUser(Role::ROLE_ADMIN),
                [],
                415,
                null,
                null,
                [], // Without file
            ],
        ];
    }

    public function getDataProvider(): iterable
    {
        $user = $this->getUser(Role::ROLE_CONTRIBUTOR);

        return [
            [null, 1, ['id' => 1], 401],
            [$user, 1, ['id' => 1], 200],
            [$this->getUser(Role::ROLE_ADMIN), 2, ['id' => 2], 200],
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
            [null, 2, 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 2, 200],
            [$this->getUser(Role::ROLE_ADMIN), 2, 200],
        ];
    }

    public function patchUpdateDataProvider(): iterable
    {
        return [
            [null, 2, ['filePath' => 'not_possible.csv'], 405],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 2, ['filePath' => 'not_possible.csv'], 405],
            [$this->getUser(Role::ROLE_ADMIN), 2, ['filePath' => 'not_possible.csv'], 405],
        ];
    }
}
