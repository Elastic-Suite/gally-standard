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

namespace Gally\Search\Tests\Api\GraphQl;

use Gally\Search\Elasticsearch\Request\BucketInterface;
use Gally\Search\Tests\Api\Rest\FacetConfigurationTest as RestFacetConfigurationTest;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestGraphQlToTest;
use Gally\User\Constant\Role;
use Gally\User\Entity\User;
use Symfony\Contracts\HttpClient\ResponseInterface;

class FacetConfigurationTest extends RestFacetConfigurationTest
{
    /**
     * @dataProvider updateDataProvider
     *
     * @depends testGetCollectionBefore
     */
    public function testUpdateValue(?User $user, string $id, array $newData, int $expectedStatus, ?string $expectedMessage)
    {
        $query = '';
        foreach ($newData as $key => $value) {
            $query .= "\n$key: " . (\is_string($value) ? "\"$value\"" : $value);
        }

        $expectedResponse = 200 != $expectedStatus
            ? new ExpectedResponse(200, function (ResponseInterface $response) use ($expectedMessage) {
                $this->assertJsonContains(['errors' => [['message' => $expectedMessage]]]);
            })
            : new ExpectedResponse(200);

        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    mutation {
                      updateFacetConfiguration(input: {
                        id: "{$this->getUri('facet_configurations', $id)}" $query
                      }) {
                        facetConfiguration { id coverageRate sortOrder }
                      }
                    }
                GQL,
                $user
            ),
            $expectedResponse
        );
    }

    protected function testGetCollection(?User $user, ?string $entityType, ?string $categoryId, array $items, int $responseCode, ?string $expectedMessage = null): void
    {
        $expectedResponse = 200 != $responseCode
            ? new ExpectedResponse(200, function (ResponseInterface $response) use ($expectedMessage) {
                $this->assertJsonContains(['errors' => [['message' => $expectedMessage]]]);
            })
            : new ExpectedResponse(
                200,
                function (ResponseInterface $response) use ($items) {
                    $this->assertJsonContains(
                        [
                            'data' => [
                                'facetConfigurations' => [
                                    'paginationInfo' => ['totalCount' => \count($items)],
                                ],
                            ],
                        ]
                    );

                    $responseData = $response->toArray();

                    foreach ($items as $item) {
                        $item = $this->completeContent($item);
                        $this->assertEquals(
                            $item,
                            $this->getById($item['id'], $responseData['data']['facetConfigurations']['collection'])
                        );
                    }
                }
            );

        $query = $entityType ? ["sourceField__metadata__entity: \"$entityType\""] : [];

        if ($categoryId) {
            $query[] = "category: \"/categories/$categoryId\"";
        }
        $query = empty($query) ? '' : ('(' . implode(' ', $query) . ')');
        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                      facetConfigurations $query {
                        collection {
                            id
                            displayMode
                            coverageRate
                            maxSize
                            isVirtual
                            sortOrder
                            position
                            defaultCoverageRate
                            defaultMaxSize
                            defaultSortOrder
                            defaultPosition
                            category { id }
                            sourceField { id defaultLabel }
                        }
                        paginationInfo {totalCount}
                      }
                    }
                GQL,
                $user
            ),
            $expectedResponse
        );
    }

    /**
     * @dataProvider getDataProvider
     *
     * @depends testGetCollectionAfter
     */
    public function testGet(?User $user, int|string $id, array $expectedData, int $responseCode, ?string $expectedMessage = null): void
    {
        $expectedResponse = 200 != $responseCode
            ? new ExpectedResponse(200, function (ResponseInterface $response) use ($expectedMessage) {
                $this->assertJsonContains(['errors' => [['message' => $expectedMessage]]]);
            })
            : new ExpectedResponse(
                200,
                function (ResponseInterface $response) use ($expectedData) {
                    $this->assertJsonContains(
                        [
                            'data' => [
                                'facetConfiguration' => $expectedData,
                            ],
                        ]
                    );
                }
            );

        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                      facetConfiguration (id: "{$this->getUri('facet_configurations', $id)}") {
                        id
                      }
                    }
                GQL,
                $user
            ),
            $expectedResponse,
        );
    }

    public function getDataProvider(): iterable
    {
        $user = $this->getUser(Role::ROLE_CONTRIBUTOR);

        return [
            [null, '3-0', ['id' => $this->getUri('facet_configurations', '3-0')], 401, 'Access Denied.'],
            [$user, '3-0', ['id' => $this->getUri('facet_configurations', '3-0')], 200],
            [$this->getUser(Role::ROLE_ADMIN), '3-0', ['id' => $this->getUri('facet_configurations', '3-0')], 200],
        ];
    }

    /**
     * @dataProvider deleteDataProvider
     *
     * @depends testGet
     */
    public function testDelete(?User $user, int|string $id, int $responseCode, ?string $expectedMessage = null): void
    {
        $expectedResponse = 204 != $responseCode
            ? new ExpectedResponse(200, function (ResponseInterface $response) use ($expectedMessage) {
                $this->assertJsonContains(['errors' => [['message' => $expectedMessage]]]);
            })
            : new ExpectedResponse(
                200,
                function (ResponseInterface $response) use ($id) {
                    $this->assertJsonContains(
                        [
                            'data' => [
                                'deleteFacetConfiguration' => [
                                    'facetConfiguration' => ['id' => $this->getUri('facet_configurations', $id)],
                                ],
                            ],
                        ]
                    );
                }
            );

        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    mutation {
                      deleteFacetConfiguration(input: {id: "{$this->getUri('facet_configurations', $id)}"}) {
                        facetConfiguration {
                          id
                        }
                      }
                    }
                GQL,
                $user
            ),
            $expectedResponse,
        );
    }

    protected function completeContent(array $data): array
    {
        $sourceFieldId = $data['sourceField'];
        $sourceFieldLabel = $data['sourceFieldLabel'];
        $categoryId = $data['category'] ?? 0;
        unset($data['sourceField']);
        unset($data['category']);
        $id = implode('-', [$sourceFieldId, $categoryId]);

        $baseData = [
            'id' => $this->getUri('facet_configurations', $id),
            'sourceField' => [
                'id' => $this->getUri('source_fields', $sourceFieldId),
                'defaultLabel' => $sourceFieldLabel,
            ],
            'category' => null,
            'displayMode' => 'auto',
            'coverageRate' => 90,
            'maxSize' => 10,
            'isVirtual' => false,
            'sortOrder' => BucketInterface::SORT_ORDER_COUNT,
            'position' => null,
            'defaultCoverageRate' => 90,
            'defaultMaxSize' => 10,
            'defaultSortOrder' => BucketInterface::SORT_ORDER_COUNT,
            'defaultPosition' => null,
            'sourceFieldLabel' => $sourceFieldLabel,
        ];

        if ($categoryId) {
            $baseData['category'] = [
                'id' => $this->getUri('categories', $categoryId),
            ];
        }

        $data = array_merge($baseData, $data);
        unset($data['sourceFieldLabel']);

        return $data;
    }

    protected function getById(string $id, array $list): ?array
    {
        foreach ($list as $element) {
            if ($id === $element['id']) {
                return $element;
            }
        }

        return null;
    }
}
