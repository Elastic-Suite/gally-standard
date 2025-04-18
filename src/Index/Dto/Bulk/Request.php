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

namespace Gally\Index\Dto\Bulk;

use Gally\Index\Entity\Index;

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
     * Add a single document to the index.
     */
    public function addDocument(Index $index, string|int|null $docId, array $data): self
    {
        $this->bulkData[] = ['index' => ['_index' => $index->getName(), '_id' => $docId]];
        $this->bulkData[] = $data;

        return $this;
    }

    /**
     * Add a several documents to the index.
     */
    public function addDocuments(Index $index, array $data): self
    {
        array_walk(
            $data,
            function ($documentData) use ($index) {
                $identifier = $documentData['entity_id'] ?? $documentData['id'] ?? null;
                $this->addDocument($index, $identifier, $documentData);
            }
        );

        return $this;
    }

    /**
     * Update a document in the index.
     */
    public function updateDocument(Index $index, string|int|null $docId, array $data): self
    {
        $this->bulkData[] = ['update' => ['_index' => $index->getName(), '_id' => $docId]];
        $this->bulkData[] = ['doc' => $data];

        return $this;
    }

    /**
     * Update multiple documents in the index.
     */
    public function updateDocuments(Index $index, array $data): self
    {
        array_walk(
            $data,
            function ($documentData) use ($index) {
                $identifier = $documentData['entity_id'] ?? $documentData['id'] ?? null;
                $this->updateDocument($index, $identifier, $documentData);
            }
        );

        return $this;
    }

    /**
     * Delete a document from the index.
     */
    public function deleteDocument(Index $index, string|int $docId): self
    {
        $this->bulkData[] = ['delete' => ['_index' => $index->getName(), '_id' => $docId]];

        return $this;
    }

    /**
     * Delete multiple documents from the index.
     */
    public function deleteDocuments(Index $index, array $documentIds): self
    {
        array_walk(
            $documentIds,
            function ($identifier) use ($index) {
                $this->deleteDocument($index, $identifier);
            }
        );

        return $this;
    }
}
