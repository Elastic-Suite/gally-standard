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

namespace Gally\Index\Tests\Api\GraphQl;

use Gally\Index\Tests\Api\Rest\IndexDocumentTest as RestIndexDocumentTest;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestGraphQlToTest;
use Gally\User\Model\User;
use Symfony\Contracts\HttpClient\ResponseInterface;

class IndexDocumentTest extends RestIndexDocumentTest
{
    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(
        ?User $user,
        array $data,
        int $responseCode = 201,
        ?string $message = null,
    ): void {
        $documents = json_encode($data['documents']);
        $request = new RequestGraphQlToTest(
            <<<GQL
                mutation {
                  createIndexDocument(input: {indexName: "{$data['indexName']}" documents: {$documents}})
                  {
                    indexDocument {
                      id
                      indexName
                    }
                  }
                }
                GQL,
            $user
        );

        $expectedResponse = 201 != $responseCode
            ? new ExpectedResponse(200, function (ResponseInterface $response) use ($message) {
                $this->assertGraphQlError($message);
            })
            : new ExpectedResponse(200, function (ResponseInterface $response) use ($data) {
                $this->assertJsonContains(
                    [
                        'data' => [
                            'createIndexDocument' => ['indexDocument' => ['indexName' => $data['indexName']]],
                        ],
                    ]
                );
            });

        $this->validateApiCall($request, $expectedResponse);
    }
}
