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

use Gally\Catalog\Entity\LocalizedCatalog;
use Gally\Index\Entity\IndexStateManagement;
use Gally\Metadata\Entity\Metadata;

interface IndexStateManagementRepositoryInterface
{
    /**
     * Creates an ism for a given entity metadata and localized catalog.
     *
     * @param Metadata         $metadata         Entity metadata
     * @param LocalizedCatalog $localizedCatalog LocalizedCatalog
     */
    public function createForEntity(
        Metadata $metadata,
        LocalizedCatalog $localizedCatalog,
    ): IndexStateManagement;

    /**
     * Creates an ism.
     *
     * @param string           $identifier       Ism identifier
     * @param LocalizedCatalog $localizedCatalog Localized catalog
     * @param array            $indexPatterns    Index patterns
     * @param int              $rolloverAfter    Ism rollover value (in days)
     * @param int              $deleteAfter      Ism delete value (in days)
     * @param string|null      $description      Ism description
     * @param int|null         $priority         Ism pattern priority
     */
    public function create(
        string $identifier,
        LocalizedCatalog $localizedCatalog,
        array $indexPatterns,
        int $rolloverAfter,
        int $deleteAfter,
        ?string $description = null,
        ?int $priority = null,
    ): IndexStateManagement;

    public function findByMetadata(Metadata $metadata, LocalizedCatalog $localizedCatalog): ?IndexStateManagement;

    public function findByName(string $name, LocalizedCatalog $localizedCatalog): ?IndexStateManagement;

    /**
     * @return IndexStateManagement[]
     */
    public function findAll(LocalizedCatalog $localizedCatalog): array;

    public function update(IndexStateManagement $policy): IndexStateManagement;

    public function delete(string $id): void;
}
