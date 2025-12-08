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

namespace Gally\Index\Repository\DataStream;

use Gally\Catalog\Entity\LocalizedCatalog;
use Gally\Index\Dto\Bulk;
use Gally\Index\Entity\DataStream;
use Gally\Metadata\Entity\Metadata;

interface DataStreamRepositoryInterface
{
    /**
     * Create a data stream in OpenSearch.
     */
    public function createForEntity(
        Metadata $metadata,
        LocalizedCatalog $localizedCatalog
    ): DataStream;

    /**
     * Create a data stream in OpenSearch.
     */
    public function create(
        string $identifier,
        LocalizedCatalog $localizedCatalog
    ): DataStream;

    /**
     * Find data stream by metadata.
     */
    public function findByMetadata(Metadata $metadata, LocalizedCatalog $localizedCatalog): ?DataStream;

    /**
     * Find a specific data stream identified by its name.
     */
    public function findByName(string $name, LocalizedCatalog $localizedCatalog): ?DataStream;

    /**
     * Find a specific data stream identified by its id.
     */
    public function findById(string $identifier): ?DataStream;

    /**
     * List all data streams.
     *
     * @return DataStream[]
     */
    public function findAll(): array;

    /**
     * Send bulk to index.
     *
     * @param Bulk\Request $request bulk request
     */
    public function bulk(Bulk\Request $request, bool $instantRefresh = false): Bulk\Response;

    /**
     * Delete a given data stream.
     *
     * @param string $id data stream id
     */
    public function delete(string $id): void;
}
