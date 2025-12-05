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
     * Creates an index template for a given entity metadata and catalog.
     *
     * @param Metadata         $metadata         Entity metadata
     * @param LocalizedCatalog $localizedCatalog LocalizedCatalog
     */
    public function createForEntity(
        Metadata $metadata,
        LocalizedCatalog $localizedCatalog,
    ): IndexTemplate;

    /**
     * Creates an index template.
     *
     * @param string           $indexIdentifier  Index identifier
     * @param LocalizedCatalog $localizedCatalog LocalizedCatalog
     * @param array            $indexSettings    Index settings
     * @param array            $mappings         Index mappings
     * @param bool             $isDataStream     Is index template for data stream
     * @param int|null         $priority         Ism pattern priority
     */
    public function create(
        string $indexIdentifier,
        LocalizedCatalog $localizedCatalog,
        array $indexPatterns,
        array $indexSettings,
        array $mappings,
        bool $isDataStream = false,
        ?int $priority = null,
    ): IndexTemplate;

    public function findByMetadata(Metadata $metadata, LocalizedCatalog $localizedCatalog): ?IndexTemplate;

    public function findByName(string $name, LocalizedCatalog $localizedCatalog): ?IndexTemplate;

    /**
     * @return IndexTemplate[]
     */
    public function findAll(): array;

    public function update(IndexTemplate $template): IndexTemplate;

    public function delete(string $id): void;
}
