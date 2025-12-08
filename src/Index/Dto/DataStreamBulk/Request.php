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

namespace Gally\Index\Dto\DataStreamBulk;

use Gally\Index\Entity\DataStream;

/**
 * Implementation for ES bulk request.
 */
class Request
{
    /**
     * Bulk operation stack.
     */
    private array $bulkData = [];

    /**
     * Indicates if the current bulk contains operation.
     */
    public function isEmpty(): bool
    {
        return 0 == \count($this->bulkData);
    }

    /**
     * Return list of operations to be executed as an array.
     */
    public function getOperations(): array
    {
        return $this->bulkData;
    }

    /**
     * Add a single document to the data stream.
     */
    public function addDocument(DataStream $dataStream, array $data): self
    {
        $this->bulkData[] = ['create' => ['_index' => $dataStream->getName()]];
        $this->bulkData[] = $data;

        return $this;
    }

    /**
     * Add a several documents to the data stream.
     */
    public function addDocuments(DataStream $dataStream, array $data): self
    {
        array_walk(
            $data,
            function ($documentData) use ($dataStream) {
                $this->addDocument($dataStream, $documentData);
            }
        );

        return $this;
    }
}
