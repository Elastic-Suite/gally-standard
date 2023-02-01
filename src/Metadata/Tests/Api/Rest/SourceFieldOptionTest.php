<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @package   Gally
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Gally\Metadata\Tests\Api\Rest;

use Gally\Metadata\Model\SourceFieldOption;
use Gally\Test\AbstractEntityTestWithUpdate;
use Gally\User\Constant\Role;

class SourceFieldOptionTest extends AbstractEntityTestWithUpdate
{
    protected static function getFixtureFiles(): array
    {
        return [
            __DIR__ . '/../../fixtures/source_field_option.yaml',
            __DIR__ . '/../../fixtures/source_field.yaml',
            __DIR__ . '/../../fixtures/metadata.yaml',
        ];
    }

    protected function getEntityClass(): string
    {
        return SourceFieldOption::class;
    }

    /**
     * {@inheritDoc}
     */
    public function createDataProvider(): iterable
    {
        $adminUser = $this->getUser(Role::ROLE_ADMIN);

        return [
            [null, ['sourceField' => '/source_fields/4', 'code' => 'A', 'position' => 10, 'defaultLabel' => 'label'], 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), ['sourceField' => '/source_fields/4', 'code' => 'A', 'position' => 10, 'defaultLabel' => 'label'], 403],
            [$adminUser, ['sourceField' => '/source_fields/4', 'code' => 'A', 'position' => 10, 'defaultLabel' => 'label'], 201],
            [$adminUser, ['sourceField' => '/source_fields/4', 'code' => 'B', 'defaultLabel' => 'label'], 201],
            [$adminUser, ['position' => 3, 'code' => 'A', 'defaultLabel' => 'label'], 422, 'sourceField: This value should not be blank.'],
            [$adminUser, ['sourceField' => '/source_fields/4', 'position' => 3, 'defaultLabel' => 'label'], 422, 'code: This value should not be blank.'],
            [$adminUser, ['sourceField' => '/source_fields/4', 'position' => 3, 'code' => 'C'], 422, 'defaultLabel: This value should not be blank.'],
            [$adminUser, ['sourceField' => '/source_fields/4', 'code' => 'A', 'position' => 3, 'defaultLabel' => 'label'], 422, 'sourceField: An option with this code is already defined for this sourceField.'],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getDataProvider(): iterable
    {
        $user = $this->getUser(Role::ROLE_CONTRIBUTOR);

        return [
            [null, 1, ['id' => 1], 401],
            [$user, 1, ['id' => 1], 200],
            [$this->getUser(Role::ROLE_ADMIN), 1, ['id' => 1], 200],
            [$user, 2, ['id' => 2], 200],
            [$user, 10, [], 404],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function deleteDataProvider(): iterable
    {
        $adminUser = $this->getUser(Role::ROLE_ADMIN);

        return [
            [null, 1, 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 1, 403],
            [$adminUser, 1, 204],
            [$adminUser, 2, 204],
            [$adminUser, 10, 404],
        ];
    }

    /**
     * {@inheritDoc}
     */
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
            [null, 1, ['defaultLabel' => 'label PATCH/PUT'], 401],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 1,  ['defaultLabel' => 'label PATCH/PUT'], 403],
            [$this->getUser(Role::ROLE_ADMIN), 1, ['defaultLabel' => 'label PATCH/PUT'], 200],
        ];
    }
}
