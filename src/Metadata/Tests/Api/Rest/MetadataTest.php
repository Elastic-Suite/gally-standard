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

namespace Gally\Metadata\Tests\Api\Rest;

use Gally\Metadata\Entity\Metadata;
use Gally\Test\AbstractEntityTestWithUpdate;
use Gally\User\Constant\Role;

class MetadataTest extends AbstractEntityTestWithUpdate
{
    protected static function getFixtureFiles(): array
    {
        return [__DIR__ . '/../../fixtures/metadata.yaml'];
    }

    protected function getEntityClass(): string
    {
        return Metadata::class;
    }

    public function createDataProvider(): iterable
    {
        $adminUser = $this->getUser(Role::ROLE_ADMIN);

        return [
            [null, ['entity' => 'article'], 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), ['entity' => 'article'], 403],
            [$adminUser, ['entity' => 'article']],
            [$adminUser, ['entity' => 'author']],
            [$adminUser, ['entity' => ''], 422, 'entity: This value should not be blank.'],
            [$adminUser, ['entity' => 'product'], 422, 'entity: This value is already used.'],
            [$adminUser, ['entity' => 'category'], 422, 'entity: This value is already used.'],
        ];
    }

    public function getDataProvider(): iterable
    {
        $user = $this->getUser(Role::ROLE_CONTRIBUTOR);

        return [
            [null, 4, ['id' => 4, 'entity' => 'product'], 401],
            [$this->getUser(Role::ROLE_ADMIN), 4, ['id' => 4, 'entity' => 'product'], 200],
            [$user, 4, ['id' => 4, 'entity' => 'product'], 200],
            [$user, 6, ['id' => 6, 'entity' => 'article'], 200],
            [$user, 8, [], 404],
        ];
    }

    public function deleteDataProvider(): iterable
    {
        $adminUser = $this->getUser(Role::ROLE_ADMIN);

        return [
            [null, 4, 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 4, 403],
            [$adminUser, 4, 204],
            [$adminUser, 6, 204],
            [$adminUser, 8, 404],
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
            [null, 5, ['entity' => 'article PATCH'], 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 5, ['entity' => 'article PATCH'], 403],
            [$this->getUser(Role::ROLE_ADMIN), 5, ['entity' => 'article PATCH'], 200],
        ];
    }

    public function putUpdateDataProvider(): iterable
    {
        return [
            [null, 5, ['entity' => 'article PUT'], 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 5, ['entity' => 'article PUT'], 403],
            [$this->getUser(Role::ROLE_ADMIN), 5, ['entity' => 'article PUT'], 200],
        ];
    }
}
