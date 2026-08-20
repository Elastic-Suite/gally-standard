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

namespace Gally\Fixture\Service;

interface EntityTransformsFixturesInterface
{
    /**
     * Removes OpenSearch Transforms targeting a given entity type's index, for one specific
     * catalog or all of them. Scoped to the entity's own target index (rather than deleting every
     * transform in the cluster) so it is safe to call even when unrelated transforms exist.
     *
     * @param int|string|null $localizedCatalogIdentifier Catalog identifier (code or id) to limit the deletion to
     */
    public function deleteEntityElasticsearchTransforms(string $entityType, int|string|null $localizedCatalogIdentifier = null): void;
}
