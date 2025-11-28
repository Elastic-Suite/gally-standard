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

namespace Gally\Index\Api;

use Gally\Catalog\Entity\LocalizedCatalog;
use Gally\Index\Entity\Index;
use Gally\Metadata\Entity\Metadata;

interface IndexSettingsInterface
{
    /**
     * Returns the index alias for an identifier (eg. product) by catalog.
     *
     * @param string                      $indexIdentifier  Index identifier
     * @param int|string|LocalizedCatalog $localizedCatalog Localized catalog
     */
    public function getIndexAliasFromIdentifier(string $indexIdentifier, LocalizedCatalog|int|string $localizedCatalog): string;

    /**
     * Create a new index for an identifier (eg. product) by catalog including current date.
     *
     * @param string                      $indexIdentifier  Index identifier
     * @param int|string|LocalizedCatalog $localizedCatalog Localized catalog
     */
    public function createIndexNameFromIdentifier(string $indexIdentifier, LocalizedCatalog|int|string $localizedCatalog): string;

    /**
     * Create a new ism name for an identifier (eg. product) by localized catalog.
     *
     * @param string           $identifier       Ism identifier
     * @param LocalizedCatalog $localizedCatalog Localized catalog
     */
    public function createIsmNameFromIdentifier(string $identifier, LocalizedCatalog $localizedCatalog): string;

    /**
     * Return the index aliases to set to a newly created index for an identifier (eg. product) by catalog.
     *
     * @param string                      $indexIdentifier  An index identifier
     * @param LocalizedCatalog|int|string $localizedCatalog Localized catalog
     *
     * @return string[]
     */
    public function getNewIndexMetadataAliases(string $indexIdentifier, LocalizedCatalog|int|string $localizedCatalog): array;

    /**
     * Load analysis settings by catalog.
     *
     * @param int|string|LocalizedCatalog $localizedCatalog Localized catalog
     *
     * @return array<mixed>
     */
    public function getAnalysisSettings(LocalizedCatalog|int|string $localizedCatalog): array;

    /**
     * Returns settings used during index creation.
     *
     * @return array<mixed>
     */
    public function getCreateIndexSettings(): array;

    /**
     * Returns settings used when installing an index.
     *
     * @return array<mixed>
     */
    public function getInstallIndexSettings(): array;

    /**
     * Returns the list of the available indices declared in gally_indices.xml.
     *
     * @return array<mixed>
     */
    public function getIndicesConfig(): array;

    /**
     * Return config of an index.
     *
     * @param string $indexIdentifier index identifier
     *
     * @return array<mixed>
     */
    public function getIndexConfig(string $indexIdentifier): array;

    /**
     * Get dynamic index settings per catalog (language).
     *
     * @return array<mixed>
     */
    public function getDynamicIndexSettings(Metadata $metadata, LocalizedCatalog|int|string $localizedCatalog): array;

    /**
     * Extract original entity from index metadata aliases.
     */
    public function extractEntityFromAliases(Index $index): ?string;

    /**
     * Extract original catalog id from index metadata aliases.
     *
     * @throws \Exception
     */
    public function extractCatalogFromAliases(Index $index): ?LocalizedCatalog;

    /**
     * Check if index name follow the naming convention.
     */
    public function isInternal(Index $index): bool;

    /**
     * Check if index has been installed.
     */
    public function isInstalled(Index $index): bool;

    /**
     * Check if index is obsolete.
     */
    public function isObsolete(Index $index): bool;

    /**
     * Get the ISM rollover_after value from the configuration.
     *
     * @param LocalizedCatalog $localizedCatalog Localized catalog
     * @param Metadata|null    $metadata         Optional metadata to check for entity-specific configuration
     */
    public function getIsmRolloverAfter(LocalizedCatalog $localizedCatalog, ?Metadata $metadata = null): ?int;

    /**
     * Get the ISM delete_after value from the configuration.
     *
     * @param LocalizedCatalog $localizedCatalog Localized catalog
     * @param Metadata|null    $metadata         Optional metadata to check for entity-specific configuration
     */
    public function getIsmDeleteAfter(LocalizedCatalog $localizedCatalog, ?Metadata $metadata = null): ?int;
}
