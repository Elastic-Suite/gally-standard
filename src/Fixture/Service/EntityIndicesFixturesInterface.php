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

interface EntityIndicesFixturesInterface
{
    /**
     * Creates and installs Elasticsearch indices through the API for a given entity type and one specific catalog or all of them.
     *
     * @param string          $entityType                 Entity type
     * @param string|int|null $localizedCatalogIdentifier Catalog identifier (code or id) to limit the index creation to
     */
    public function createEntityElasticsearchIndices(string $entityType, string|int|null $localizedCatalogIdentifier = null): void;

    /**
     * Removes installed Elasticsearch indices through the API for a given entity type and one specific catalog or all of them.
     *
     * @param int|string|null $localizedCatalogIdentifier Catalog identifier (code or id) to limit to the index deletion to
     */
    public function deleteEntityElasticsearchIndices(string $entityType, int|string|null $localizedCatalogIdentifier = null): void;
}
