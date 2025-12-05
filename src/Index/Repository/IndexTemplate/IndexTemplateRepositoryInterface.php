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

namespace Gally\Index\Repository\IndexTemplate;

use Gally\Catalog\Entity\LocalizedCatalog;
use Gally\Index\Entity\IndexTemplate;
use Gally\Metadata\Entity\Metadata;

interface IndexTemplateRepositoryInterface
{
    /**
     * Creates an index for a given entity metadata and catalog.
     *
     * @param Metadata                    $metadata         Entity metadata
     * @param int|string|LocalizedCatalog $localizedCatalog LocalizedCatalog
     */
    public function createEntityIndexTemplate(
        Metadata $metadata,
        LocalizedCatalog|int|string $localizedCatalog,
        array $indexPatterns,
    ): IndexTemplate;

    /**
     * Creates an index for a given entity metadata and catalog.
     *
     * @param string                      $indexIdentifier  Index identifier
     * @param int|string|LocalizedCatalog $localizedCatalog LocalizedCatalog
     * @param array                       $indexSettings    Index settings
     */
    public function createIndexTemplate(
        string $indexIdentifier,
        LocalizedCatalog|int|string $localizedCatalog,
        array $indexPatterns,
        array $indexSettings,
        array $mappings,
        bool $isDataStream = false,
        ?int $priority = null,
    ): IndexTemplate;

    /**
     * @return IndexTemplate[]
     */
    public function findAll(): array;

    public function findByName(string $name): ?IndexTemplate;

    public function save(IndexTemplate $template): IndexTemplate;

    public function delete(string $name): void;
}
