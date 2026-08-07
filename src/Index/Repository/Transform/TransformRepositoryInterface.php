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

namespace Gally\Index\Repository\Transform;

use Gally\Index\Entity\Transform;

/**
 * Generic access to the OpenSearch Transform API (index-management plugin). Entity-agnostic:
 * callers provide a Transform definition, this repository only knows how to talk to OpenSearch.
 */
interface TransformRepositoryInterface
{
    /**
     * Create or update a transform definition. Stops it first if it is currently running:
     * OpenSearch does not allow redefining a running transform in place.
     */
    public function createOrUpdate(Transform $transform): void;

    public function start(string $transformId): void;

    public function stop(string $transformId): void;

    public function delete(string $transformId): void;

    /**
     * @return array<mixed>
     */
    public function explain(string $transformId): array;

    /**
     * @return Transform[]
     */
    public function findAll(): array;

    /**
     * Stop and delete every transform.
     */
    public function deleteAll(): void;
}
