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
            $request->addDocument($index, $documentData['id'] ?? $documentData['entity_id'], $documentData);
        }

        if (!$request->isEmpty()) {
            $this->indexRepository->bulk($request, $instantRefresh);
        }
    }

    public function delete(string $indexName, array $documents): void
    {
        /**
         * @Todo: Implement the right way to delete a Document
         */
//        foreach ($documents as $document) { // @phpstan-ignore-line
//            $response = $this->client->delete([
//                'index' => $indexName,
//                'id' => $document['entity_id'] ?? $document['id'],
//            ]);
//        }
    }
}
