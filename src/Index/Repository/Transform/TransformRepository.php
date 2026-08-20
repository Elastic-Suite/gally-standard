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

namespace Gally\Index\Repository\Transform;

use Gally\Index\Entity\Transform;
use OpenSearch\Client;

class TransformRepository implements TransformRepositoryInterface
{
    private const API_PATH = '/_plugins/_transform';

    public function __construct(
        private Client $client,
    ) {
    }

    public function createOrUpdate(Transform $transform): void
    {
        // OpenSearch requires if_seq_no/if_primary_term to PUT over an existing transform;
        // deleting first (stop is a prerequisite for delete) and recreating is simpler and
        // avoids having to fetch/track that version state ourselves.
        $this->stopSilently($transform->getId());
        $this->deleteSilently($transform->getId());
        $this->performRequest('PUT', "/{$transform->getId()}", [], ['transform' => $transform->toArray()]);
    }

    public function start(string $transformId): void
    {
        $this->performRequest('POST', "/{$transformId}/_start");
    }

    public function stop(string $transformId): void
    {
        $this->performRequest('POST', "/{$transformId}/_stop");
    }

    public function delete(string $transformId): void
    {
        $this->performRequest('DELETE', "/{$transformId}");
    }

    public function explain(string $transformId): array
    {
        return (array) $this->performRequest('GET', "/{$transformId}/_explain");
    }

    public function findAll(): array
    {
        $response = (array) $this->performRequest('GET');

        return array_map(
            fn (array $item) => Transform::fromResponse($item['_id'], $item['transform']),
            $response['transforms'] ?? []
        );
    }

    public function deleteAll(): void
    {
        foreach ($this->findAll() as $transform) {
            $this->stopSilently($transform->getId());
            $this->delete($transform->getId());
        }
    }

    private function stopSilently(string $transformId): void
    {
        try {
            $this->stop($transformId);
        } catch (\Exception) {
            // Transform doesn't exist yet, nothing to stop.
        }
    }

    private function deleteSilently(string $transformId): void
    {
        try {
            $this->delete($transformId);
        } catch (\Exception) {
            // Transform doesn't exist yet, nothing to delete.
        }
    }

    private function performRequest(string $method, string $uri = '', array $params = [], $body = null)
    {
        $response = $this->client->transport->performRequest(
            $method,
            self::API_PATH . $uri,
            $params,
            $body
        );

        return $this->client->transport->resultOrFuture($response, []);
    }
}
