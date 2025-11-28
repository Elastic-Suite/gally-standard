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

namespace Gally\Index\Repository\IndexStateManagement;

use Gally\Index\Entity\IndexStateManagement;

interface IndexStateManagementRepositoryInterface
{
    /**
     * @return IndexStateManagement[]
     */
    public function findAll(): array;

    public function findById(string $id): ?IndexStateManagement;

    public function save(IndexStateManagement $policy): IndexStateManagement;

    public function delete(string $id): void;
}
