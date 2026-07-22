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

/**
 * Generic access to the OpenSearch Transform API (index-management plugin). Entity-agnostic:
 * callers provide the transform id and its full definition (source/target index, groups,
 * aggregations, schedule...), this repository only knows how to talk to OpenSearch.
 */
interface TransformRepositoryInterface
{
    /**
     * Create or update a transform definition. Stops it first if it is currently running:
     * OpenSearch does not allow redefining a running transform in place.
     *
     * @param string $transformId Transform id
     * @param array  $definition  Transform definition (everything under the "transform" key)
     */
    public function createOrUpdate(string $transformId, array $definition): void;

    public function start(string $transformId): void;

    public function stop(string $transformId): void;

    public function delete(string $transformId): void;

    /**
     * @return array<mixed>
     */
    public function explain(string $transformId): array;

    /**
     * Resolve the concrete (physical) index name currently behind an alias.
     * The Transform API refuses to write to an alias as target_index, so callers need this to
     * resolve whatever alias Gally manages (blue/green reindex) to the actual index name.
     */
    public function resolveConcreteIndexName(string $alias): ?string;
}
