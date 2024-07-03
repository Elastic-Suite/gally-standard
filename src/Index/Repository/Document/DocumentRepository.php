<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @package   Gally
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Gally\Index\Repository\Document;

use ApiPlatform\Exception\InvalidArgumentException;
use Gally\Index\Dto\Bulk;
use Gally\Index\Repository\Index\IndexRepository;

class DocumentRepository implements DocumentRepositoryInterface
{
    public function __construct(
        private IndexRepository $indexRepository,
    ) {
    }

    public function index(string $indexName, array $documents, bool $instantRefresh = false): void
    {
        $request = new Bulk\Request();
        $index = $this->indexRepository->findByName($indexName);
        foreach ($documents as $document) {
            $documentData = json_decode($document, true);
            $identifier = $documentData['entity_id'] ?? $documentData['id'] ?? null;
            $request->addDocument($index, $identifier, $documentData);
        }

        $this->runBulk($request, $instantRefresh);
    }

    public function delete(string $indexName, array $documentIds): void
    {
        $request = new Bulk\Request();
        $index = $this->indexRepository->findByName($indexName);

        if (!$index) {
            throw new InvalidArgumentException(sprintf('The index %s does not exist.', $indexName));
        }

        $request->deleteDocuments($index, $documentIds);

        $this->runBulk($request, true);
    }

    private function runBulk(Bulk\Request $request, bool $instantRefresh): Bulk\Response
    {
        if ($request->isEmpty()) {
            throw new InvalidArgumentException('Can not execute empty bulk.');
        }

        $response = $this->indexRepository->bulk($request, $instantRefresh);
        if ($response->hasErrors()) {
            $errorMessages = [];
            foreach ($response->aggregateErrorsByReason() as $error) {
                $sampleDocumentIds = implode(', ', \array_slice($error['document_ids'], 0, 10));
                $errorMessages[] = sprintf(
                    'Bulk %s operation failed %d times in index %s.',
                    $error['operation'],
                    $error['count'],
                    $error['index']
                );
                $errorMessages[] = sprintf('Error (%s) : %s.', $error['error']['type'], $error['error']['reason']);
                $errorMessages[] = sprintf('Failed doc ids sample : %s.', $sampleDocumentIds);
            }
            if (!empty($errorMessages)) {
                throw new InvalidArgumentException(implode(' ', $errorMessages));
            }
        }

        return $response;
    }
}
