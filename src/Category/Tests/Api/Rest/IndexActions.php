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

namespace Gally\Category\Tests\Api\Rest;

use Gally\Test\ExpectedResponse;
use Gally\Test\RequestToTest;
use Gally\User\Constant\Role;
use Symfony\Contracts\HttpClient\ResponseInterface;

trait IndexActions
{
    protected function createIndex(string $entityType, int $catalogId): string
    {
        $indexName = '';
        $this->validateApiCall(
            new RequestToTest(
                'POST',
                'indices',
                $this->getUser(Role::ROLE_ADMIN),
                ['entityType' => $entityType, 'localizedCatalog' => "$catalogId"]
            ),
            new ExpectedResponse(
                201,
                function (ResponseInterface $response) use (&$indexName) {
                    $responseData = $response->toArray();
                    $indexName = $responseData['name'];
                }
            )
        );

        return $indexName;
    }

    protected function installIndex(string $indexName): void
    {
        $this->validateApiCall(
            new RequestToTest('PUT', "indices/install/{$indexName}", $this->getUser(Role::ROLE_ADMIN)),
            new ExpectedResponse(200)
        );
    }

    protected function bulkIndex(string $indexName, array $data): void
    {
        $data = array_map(fn ($item) => json_encode($item), $data);
        $this->validateApiCall(
            new RequestToTest(
                'POST',
                'index_documents',
                $this->getUser(Role::ROLE_ADMIN),
                [
                    'indexName' => $indexName,
                    'documents' => $data,
                ]
            ),
            new ExpectedResponse(201)
        );
    }

    protected function bulkDeleteIndex(string $indexName, array $ids): void
    {
        $this->validateApiCall(
            new RequestToTest(
                'DELETE',
                "index_documents/$indexName",
                $this->getUser(Role::ROLE_ADMIN),
                ['document_ids' => $ids]
            ),
            new ExpectedResponse(204)
        );
    }
}
