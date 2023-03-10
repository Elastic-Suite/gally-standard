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

use Elasticsearch\Client;

class DocumentRepository implements DocumentRepositoryInterface
{
    public function __construct(
        private Client $client
    ) {
    }

    public function index(string $indexName, array $documents, bool $instantRefresh = false): void
    {
        $params = [];
        $responses = [];
        foreach ($documents as $document) {
            $document = json_decode($document, true);
            $params['body'][] = [
                'index' => [
                    '_index' => $indexName,
                    '_id' => $document['entity_id'] ?? $document['id'] ?? null,
                ],
            ];

            $params['body'][] = $document;
            if ($instantRefresh) {
                $params['refresh'] = 'wait_for';
            }
        }

        if (\count($params) > 0) {
            $responses = $this->client->bulk($params);
        }
    }

    public function delete(string $indexName, array $documents): void
    {
        return;
        /**
         * @Todo: Implement the right way to delete a Document
         */
        foreach ($documents as $document) { // @phpstan-ignore-line
            $response = $this->client->delete([
                'index' => $indexName,
                'id' => $document['entity_id'] ?? $document['id'],
            ]);
        }
    }
}
