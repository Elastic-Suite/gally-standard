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

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use Gally\Index\Entity\DataStream;
use Gally\Index\Entity\Index;

/**
 * Implementation for ES bulk request.
 */
class Request
{
    /**
     * Indices and data streams targeted by the operations, indexed by name.
     *
     * @var array<string, Index|DataStream>
     */
    private array $targets = [];

    /**
     * Documents to index (or to create for data streams), indexed by target name then by document id.
     *
     * @var array<string, array<string|int, array>>
     */
    private array $documents = [];

    /**
     * Partial documents to update, indexed by target name then by document id.
     *
     * @var array<string, array<string|int, array>>
     */
    private array $updates = [];

    /**
     * Documents to delete, indexed by target name then by document id.
     *
     * @var array<string, array<string|int, true>>
     */
    private array $deletes = [];

    /**
     * Indicates if the current bulk contains operation.
     */
    public function isEmpty(): bool
    {
        foreach ([$this->documents, $this->updates, $this->deletes] as $operationsByTarget) {
            foreach ($operationsByTarget as $operations) {
                if (!empty($operations)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Build the list of operations to be executed, as expected by the bulk API body.
     */
    public function getOperations(): array
    {
        $operations = [];
        foreach ($this->documents as $targetName => $documents) {
            $action = $this->targets[$targetName] instanceof DataStream ? 'create' : 'index';
            foreach ($documents as $documentId => $document) {
                $operations[] = [$action => ['_index' => $targetName, '_id' => (string) $documentId]];
                $operations[] = $document;
            }
        }
        foreach ($this->updates as $targetName => $updates) {
            foreach ($updates as $documentId => $document) {
                $operations[] = ['update' => ['_index' => $targetName, '_id' => (string) $documentId]];
                $operations[] = ['doc' => $document];
            }
        }
        foreach ($this->deletes as $targetName => $deletes) {
            foreach ($deletes as $documentId => $unused) {
                $operations[] = ['delete' => ['_index' => $targetName, '_id' => (string) $documentId]];
            }
        }

        if (empty($operations)) {
            throw new InvalidArgumentException('Bulk request is empty.');
        }

        return $operations;
    }

    /**
     * Return the indices and data streams targeted by the operations.
     *
     * @return array<Index|DataStream>
     */
    public function getTargets(): array
    {
        return array_values($this->targets);
    }

    /**
     * Return the documents to index or create in the given target, indexed by document id.
     *
     * @return array<string|int, array>
     */
    public function getDocuments(Index|DataStream $target): array
    {
        return $this->documents[$target->getName()] ?? [];
    }

    /**
     * Replace all the documents to index or create in the given target.
     * Listeners own the given array: they may add documents to it or remove documents from it.
     *
     * @param array<string|int, array> $documents Documents indexed by document id
     */
    public function setDocuments(Index|DataStream $target, array $documents): self
    {
        $this->documents[$target->getName()] = $documents;

        return $this;
    }

    /**
     * Add a single document to the index.
     */
    public function addDocument(Index|DataStream $index, string|int $docId, array $data): self
    {
        $this->targets[$index->getName()] = $index;
        $this->documents[$index->getName()][$docId] = $data;

        return $this;
    }

    /**
     * Add a several documents to the index.
     */
    public function addDocuments(Index|DataStream $index, array $data): self
    {
        foreach ($data as $position => $documentData) {
            // Force identifier to be string
            $identifier = $documentData['entity_id'] ?? $documentData['id'] ?? null;
            if (null === $identifier || '' === (string) $identifier) {
                throw new InvalidArgumentException(\sprintf('Document at position %s has no id or entity_id.', $position));
            }
            $identifier = (string) $identifier;
            $documentData['id'] = $identifier;
            $this->addDocument($index, $identifier, $documentData);
        }

        return $this;
    }

    /**
     * Update a document in the index.
     */
    public function updateDocument(Index $index, string|int $docId, array $data): self
    {
        $this->targets[$index->getName()] = $index;
        $this->updates[$index->getName()][$docId] = $data;

        return $this;
    }

    /**
     * Update multiple documents in the index.
     */
    public function updateDocuments(Index $index, array $data): self
    {
        foreach ($data as $position => $documentData) {
            $identifier = $documentData['entity_id'] ?? $documentData['id'] ?? null;
            if (null === $identifier || '' === (string) $identifier) {
                throw new InvalidArgumentException(\sprintf('Document at position %s has no id or entity_id.', $position));
            }
            $this->updateDocument($index, $identifier, $documentData);
        }

        return $this;
    }

    /**
     * Delete a document from the index.
     */
    public function deleteDocument(Index $index, string|int $docId): self
    {
        $this->targets[$index->getName()] = $index;
        $this->deletes[$index->getName()][$docId] = true;

        return $this;
    }

    /**
     * Delete multiple documents from the index.
     */
    public function deleteDocuments(Index|DataStream $index, array $documentIds): self
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
