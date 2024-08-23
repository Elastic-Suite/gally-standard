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

namespace Gally\Search\Tests\Api\Rest;

use Gally\Search\Elasticsearch\Request\BucketInterface;
use Gally\Test\AbstractTestCase;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestToTest;
use Gally\User\Constant\Role;
use Gally\User\Model\User;
use Symfony\Contracts\HttpClient\ResponseInterface;

class FacetConfigurationTest extends AbstractTestCase
{
    public static function setUpBeforeClass(): void
    {
        static::loadFixture([
            __DIR__ . '/../../fixtures/catalogs.yaml',
            __DIR__ . '/../../fixtures/categories.yaml',
            __DIR__ . '/../../fixtures/source_field.yaml',
            __DIR__ . '/../../fixtures/metadata_with_product.yaml',
        ]);
    }

    protected function getApiPath(): string
    {
        return '/facet_configurations';
    }

    /**
     * @dataProvider getCollectionBeforeDataProvider
     */
    public function testGetCollectionBefore(?User $user, ?string $entityType, ?string $categoryId, array $elements, int $responseCode, ?string $expectedMessage = null): void
    {
        $this->testGetCollection($user, $entityType, $categoryId, $elements, $responseCode, $expectedMessage);
    }

    protected function getCollectionBeforeDataProvider(): array
    {
        $user = $this->getUser(Role::ROLE_CONTRIBUTOR);

        return [
            [
                $this->getUser(Role::ROLE_ADMIN),
                null,
                null,
                [
                    ['sourceField' => 2, 'sourceFieldLabel' => 'Name'],
                    ['sourceField' => 3, 'sourceFieldLabel' => 'Brand'],
                    ['sourceField' => 4, 'sourceFieldLabel' => 'Color'],
                    ['sourceField' => 5, 'sourceFieldLabel' => 'Category'],
                    ['sourceField' => 6, 'sourceFieldLabel' => 'Length'],
                    ['sourceField' => 7, 'sourceFieldLabel' => 'Size'],
                    ['sourceField' => 8, 'sourceFieldLabel' => 'Weight'],
                    ['sourceField' => 15, 'sourceFieldLabel' => 'Is_eco_friendly'],
                    ['sourceField' => 16, 'sourceFieldLabel' => 'Created_at'],
                    ['sourceField' => 20, 'sourceFieldLabel' => 'Color_full'],
                    ['sourceField' => 21, 'sourceFieldLabel' => 'Manufacture_location'],
                ],
                200,
            ],
            [
                $user,
                null,
                'cat_1',
                [
                    ['sourceField' => 2, 'category' => 'cat_1', 'sourceFieldLabel' => 'Name'],
                    ['sourceField' => 3, 'category' => 'cat_1', 'sourceFieldLabel' => 'Brand'],
                    ['sourceField' => 4, 'category' => 'cat_1', 'sourceFieldLabel' => 'Color'],
                    ['sourceField' => 5, 'category' => 'cat_1', 'sourceFieldLabel' => 'Category'],
                    ['sourceField' => 6, 'category' => 'cat_1', 'sourceFieldLabel' => 'Length'],
                    ['sourceField' => 7, 'category' => 'cat_1', 'sourceFieldLabel' => 'Size'],
                    ['sourceField' => 8, 'category' => 'cat_1', 'sourceFieldLabel' => 'Weight'],
                    ['sourceField' => 15, 'category' => 'cat_1', 'sourceFieldLabel' => 'Is_eco_friendly'],
                    ['sourceField' => 16, 'category' => 'cat_1', 'sourceFieldLabel' => 'Created_at'],
                    ['sourceField' => 20, 'category' => 'cat_1', 'sourceFieldLabel' => 'Color_full'],
                    ['sourceField' => 21, 'category' => 'cat_1', 'sourceFieldLabel' => 'Manufacture_location'],
                ],
                200,
            ],
            [
                $user,
                null,
                'cat_2',
                [
                    ['sourceField' => 2, 'category' => 'cat_2', 'sourceFieldLabel' => 'Name'],
                    ['sourceField' => 3, 'category' => 'cat_2', 'sourceFieldLabel' => 'Brand'],
                    ['sourceField' => 4, 'category' => 'cat_2', 'sourceFieldLabel' => 'Color'],
                    ['sourceField' => 5, 'category' => 'cat_2', 'sourceFieldLabel' => 'Category'],
                    ['sourceField' => 6, 'category' => 'cat_2', 'sourceFieldLabel' => 'Length'],
                    ['sourceField' => 7, 'category' => 'cat_2', 'sourceFieldLabel' => 'Size'],
                    ['sourceField' => 8, 'category' => 'cat_2', 'sourceFieldLabel' => 'Weight'],
                    ['sourceField' => 15, 'category' => 'cat_2', 'sourceFieldLabel' => 'Is_eco_friendly'],
                    ['sourceField' => 16, 'category' => 'cat_2', 'sourceFieldLabel' => 'Created_at'],
                    ['sourceField' => 20, 'category' => 'cat_2', 'sourceFieldLabel' => 'Color_full'],
                    ['sourceField' => 21, 'category' => 'cat_2', 'sourceFieldLabel' => 'Manufacture_location'],
                ],
                200,
            ],
            [
                $user,
                'product',
                null,
                [
                    ['sourceField' => 3, 'sourceFieldLabel' => 'Brand'],
                    ['sourceField' => 4, 'sourceFieldLabel' => 'Color'],
                    ['sourceField' => 5, 'sourceFieldLabel' => 'Category'],
                    ['sourceField' => 6, 'sourceFieldLabel' => 'Length'],
                    ['sourceField' => 7, 'sourceFieldLabel' => 'Size'],
                    ['sourceField' => 8, 'sourceFieldLabel' => 'Weight'],
                    ['sourceField' => 15, 'sourceFieldLabel' => 'Is_eco_friendly'],
                    ['sourceField' => 16, 'sourceFieldLabel' => 'Created_at'],
                    ['sourceField' => 20, 'sourceFieldLabel' => 'Color_full'],
                    ['sourceField' => 21, 'sourceFieldLabel' => 'Manufacture_location'],
                ],
                200,
            ],
            [
                $user,
                'category',
                null,
                [
                    ['sourceField' => 2, 'sourceFieldLabel' => 'Name'],
                ],
                200,
            ],
        ];
    }

    /**
     * @dataProvider updateDataProvider
     *
     * @depends testGetCollectionBefore
     */
    public function testUpdateValue(?User $user, string $id, array $newData, int $expectedStatus, ?string $expectedMessage)
    {
        $this->validateApiCall(
            new RequestToTest('PUT', "{$this->getApiPath()}/$id", $user, $newData),
            new ExpectedResponse($expectedStatus, null, $expectedMessage)
        );

        $this->validateApiCall(
            new RequestToTest('PATCH', "{$this->getApiPath()}/$id", $user, $newData, ['Content-Type' => 'application/merge-patch+json']),
            new ExpectedResponse($expectedStatus, null, $expectedMessage)
        );
    }

    protected function updateDataProvider(): array
    {
        $admin = $this->getUser(Role::ROLE_ADMIN);

        return [
            [null, '3-0', ['coverageRate' => 0], 401, 'Access Denied.'],
            [$admin, '3-0', ['coverageRate' => 0, 'sortOrder' => 'invalidSortOrder'], 422, 'sortOrder: The value you selected is not a valid choice.'],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), '3-0', ['coverageRate' => 0, 'sortOrder' => BucketInterface::SORT_ORDER_COUNT], 200],
            [$admin, '3-0', ['coverageRate' => 1, 'maxSize' => 100, 'sortOrder' => BucketInterface::SORT_ORDER_TERM, 'position' => 1], 200],
            [$admin, '3-cat_1', ['coverageRate' => 10, 'sortOrder' => BucketInterface::SORT_ORDER_RELEVANCE], 200],
            [$admin, '4-cat_1', ['coverageRate' => 10, 'sortOrder' => BucketInterface::SORT_ORDER_MANUAL, 'position' => 1], 200],
            [$admin, '3-cat_2', ['coverageRate' => 90], 200], // Put the default value back on a sub level
        ];
    }

    /**
     * @dataProvider getCollectionAfterDataProvider
     *
     * @depends testUpdateValue
     */
    public function testGetCollectionAfter(?User $user, ?string $entityType, ?string $categoryId, array $items, int $responseCode, ?string $expectedMessage = null): void
    {
        $this->testGetCollection($user, $entityType, $categoryId, $items, $responseCode, $expectedMessage);
    }

    protected function getCollectionAfterDataProvider(): array
    {
        $user = $this->getUser(Role::ROLE_CONTRIBUTOR);

        return [
            [
                $this->getUser(Role::ROLE_ADMIN),
                null,
                null,
                [
                    ['sourceField' => 2, 'sourceFieldLabel' => 'Name'],
                    ['sourceField' => 3, 'coverageRate' => 1, 'sourceFieldLabel' => 'Brand', 'maxSize' => 100, 'sortOrder' => BucketInterface::SORT_ORDER_TERM, 'position' => 1], // product_brand.
                    ['sourceField' => 4, 'sourceFieldLabel' => 'Color'], // product_color.
                    ['sourceField' => 5, 'sourceFieldLabel' => 'Category'], // product_category.
                    ['sourceField' => 6, 'sourceFieldLabel' => 'Length'], // product_length.
                    ['sourceField' => 7, 'sourceFieldLabel' => 'Size'], // size.
                    ['sourceField' => 8, 'sourceFieldLabel' => 'Weight'], // weight.
                    ['sourceField' => 15, 'sourceFieldLabel' => 'Is_eco_friendly'],
                    ['sourceField' => 16, 'sourceFieldLabel' => 'Created_at'],
                    ['sourceField' => 20, 'sourceFieldLabel' => 'Color_full'],
                    ['sourceField' => 21, 'sourceFieldLabel' => 'Manufacture_location'],
                ],
                200,
            ],
            [
                $user,
                null,
                'cat_1',
                [
                    ['sourceField' => 2, 'category' => 'cat_1', 'sourceFieldLabel' => 'Name'],
                    ['sourceField' => 3, 'category' => 'cat_1', 'coverageRate' => 10, 'maxSize' => 100, 'defaultCoverageRate' => 1, 'defaultMaxSize' => 100, 'sourceFieldLabel' => 'Brand', 'sortOrder' => BucketInterface::SORT_ORDER_RELEVANCE, 'defaultSortOrder' => BucketInterface::SORT_ORDER_TERM, 'position' => 1, 'defaultPosition' => 1],
                    ['sourceField' => 4, 'category' => 'cat_1', 'coverageRate' => 10, 'sourceFieldLabel' => 'Color', 'sortOrder' => BucketInterface::SORT_ORDER_MANUAL, 'position' => 1],
                    ['sourceField' => 5, 'category' => 'cat_1', 'coverageRate' => 90, 'sourceFieldLabel' => 'Category'],
                    ['sourceField' => 6, 'category' => 'cat_1', 'coverageRate' => 90, 'sourceFieldLabel' => 'Length'],
                    ['sourceField' => 7, 'category' => 'cat_1', 'coverageRate' => 90, 'sourceFieldLabel' => 'Size'],
                    ['sourceField' => 8, 'category' => 'cat_1', 'coverageRate' => 90, 'sourceFieldLabel' => 'Weight'],
                    ['sourceField' => 15, 'category' => 'cat_1', 'coverageRate' => 90, 'sourceFieldLabel' => 'Is_eco_friendly'],
                    ['sourceField' => 16, 'category' => 'cat_1', 'coverageRate' => 90, 'sourceFieldLabel' => 'Created_at'],
                    ['sourceField' => 20, 'category' => 'cat_1', 'coverageRate' => 90, 'sourceFieldLabel' => 'Color_full'],
                    ['sourceField' => 21, 'category' => 'cat_1', 'coverageRate' => 90, 'sourceFieldLabel' => 'Manufacture_location'],
                ],
                200,
            ],
            [
                $user,
                null,
                'cat_2',
                [
                    ['sourceField' => 2, 'category' => 'cat_2', 'sourceFieldLabel' => 'Name'],
                    ['sourceField' => 3, 'category' => 'cat_2', 'coverageRate' => 90, 'maxSize' => 100, 'defaultCoverageRate' => 1,  'defaultMaxSize' => 100, 'sourceFieldLabel' => 'Brand', 'sortOrder' => BucketInterface::SORT_ORDER_TERM, 'defaultSortOrder' => BucketInterface::SORT_ORDER_TERM, 'position' => 1, 'defaultPosition' => 1],
                    ['sourceField' => 4, 'category' => 'cat_2', 'coverageRate' => 90, 'sourceFieldLabel' => 'Color'],
                    ['sourceField' => 5, 'category' => 'cat_2', 'coverageRate' => 90, 'sourceFieldLabel' => 'Category'],
                    ['sourceField' => 6, 'category' => 'cat_2', 'coverageRate' => 90, 'sourceFieldLabel' => 'Length'],
                    ['sourceField' => 7, 'category' => 'cat_2', 'coverageRate' => 90, 'sourceFieldLabel' => 'Size'],
                    ['sourceField' => 8, 'category' => 'cat_2', 'coverageRate' => 90, 'sourceFieldLabel' => 'Weight'],
                    ['sourceField' => 15, 'category' => 'cat_2', 'coverageRate' => 90, 'sourceFieldLabel' => 'Is_eco_friendly'],
                    ['sourceField' => 16, 'category' => 'cat_2', 'coverageRate' => 90, 'sourceFieldLabel' => 'Created_at'],
                    ['sourceField' => 20, 'category' => 'cat_2', 'coverageRate' => 90, 'sourceFieldLabel' => 'Color_full'],
                    ['sourceField' => 21, 'category' => 'cat_2', 'coverageRate' => 90, 'sourceFieldLabel' => 'Manufacture_location'],
                ],
                200,
            ],
            [
                $user,
                'product',
                null,
                [
                    ['sourceField' => 3, 'coverageRate' => 1, 'sourceFieldLabel' => 'Brand', 'maxSize' => 100, 'sortOrder' => BucketInterface::SORT_ORDER_TERM, 'position' => 1], // product_brand.
                    ['sourceField' => 4, 'sourceFieldLabel' => 'Color'], // product_color.
                    ['sourceField' => 5, 'sourceFieldLabel' => 'Category'], // product_category.
                    ['sourceField' => 6, 'sourceFieldLabel' => 'Length'], // product_length.
                    ['sourceField' => 7, 'sourceFieldLabel' => 'Size'], // size.
                    ['sourceField' => 8, 'sourceFieldLabel' => 'Weight'], // weight.
                    ['sourceField' => 15, 'sourceFieldLabel' => 'Is_eco_friendly'],
                    ['sourceField' => 16, 'sourceFieldLabel' => 'Created_at'],
                    ['sourceField' => 20, 'sourceFieldLabel' => 'Color_full'], // product_color_full.
                    ['sourceField' => 21, 'sourceFieldLabel' => 'Manufacture_location'],
                ],
                200,
            ],
            [
                null,
                'category',
                null,
                [
                    ['sourceField' => 2, 'sourceFieldLabel' => 'Name'],
                ],
                401,
                'Access Denied.',
            ],
            [
                $user,
                'category',
                null,
                [
                    ['sourceField' => 2, 'sourceFieldLabel' => 'Name'],
                ],
                200,
            ],
            [
                $this->getUser(Role::ROLE_ADMIN),
                'category',
                null,
                [
                    ['sourceField' => 2, 'sourceFieldLabel' => 'Name'],
                ],
                200,
            ],
        ];
    }

    /**
     * @dataProvider getDataProvider
     *
     * @depends testGetCollectionAfter
     */
    public function testGet(?User $user, int|string $id, array $expectedData, int $responseCode, ?string $expectedMessage = null): void
    {
        $this->validateApiCall(
            new RequestToTest('GET', "{$this->getApiPath()}/{$id}", $user),
            new ExpectedResponse(
                $responseCode,
                function (ResponseInterface $response) use ($expectedData) {
                    $shortName = 'FacetConfiguration';
                    if ($response->getStatusCode() < 400) {
                        $this->assertJsonContains(
                            array_merge(
                                [
                                    '@context' => "/contexts/$shortName",
                                    '@type' => $shortName,
                                    '@id' => $this->getApiPath() . '/' . $expectedData['id'],
                                ],
                                $expectedData
                            )
                        );
                    } else {
                        $this->assertJsonContains(['@context' => "/contexts/$shortName", '@type' => $shortName]);
                    }
                }
            )
        );
    }

    /**
     * Data provider for entity get api call
     * The data provider should return test case with :
     * - User $user: user to use in the api call
     * - int|string $id: id of the entity to get
     * - array $expectedData: expected data of the entity
     * - int $responseCode: expected response code.
     * - string $expectedMessage: expected message.
     */
    public function getDataProvider(): iterable
    {
        $user = $this->getUser(Role::ROLE_CONTRIBUTOR);

        return [
            [null, '3-0', ['id' => '3-0'], 401],
            [$user, '3-0', ['id' => '3-0'], 200],
            [$this->getUser(Role::ROLE_ADMIN), '3-0', ['id' => '3-0'], 200],
        ];
    }

    /**
     * @dataProvider deleteDataProvider
     *
     * @depends testGet
     */
    public function testDelete(?User $user, int|string $id, int $responseCode, ?string $expectedMessage = null): void
    {
        $this->validateApiCall(
            new RequestToTest('DELETE', "{$this->getApiPath()}/{$id}", $user),
            new ExpectedResponse(
                $responseCode,
            )
        );
    }

    /**
     * Data provider for entity get api call
     * The data provider should return test case with :
     * - User $user: user to use in the api call
     * - int|string $id: id of the entity to get
     * - int $responseCode: expected response code.
     * - string $expectedMessage: expected message.
     */
    public function deleteDataProvider(): iterable
    {
        return [
            [null, '3-0', 401, 'Access Denied.'],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), '3-0', 204],
            [$this->getUser(Role::ROLE_ADMIN), '3-cat_1', 204],
        ];
    }

    protected function testGetCollection(?User $user, ?string $entityType, ?string $categoryId, array $items, int $responseCode, ?string $expectedMessage = null): void
    {
        $query = $entityType ? ["sourceField.metadata.entity=$entityType"] : [];
        if ($categoryId) {
            $query[] = "category=$categoryId";
        }
        $query = empty($query) ? '' : implode('&', $query);

        $this->validateApiCall(
            new RequestToTest('GET', $this->getApiPath() . '?' . $query . '&page=1', $user),
            new ExpectedResponse(
                $responseCode,
                function (ResponseInterface $response) use ($items) {
                    $shortName = 'FacetConfiguration';
                    if ($response->getStatusCode() < 400) {
                        $this->assertJsonContains(
                            [
                                '@context' => "/contexts/$shortName",
                                '@id' => '/facet_configurations',
                                '@type' => 'hydra:Collection',
                                'hydra:totalItems' => \count($items),
                            ]
                        );

                        $responseData = $response->toArray();

                        foreach ($items as $item) {
                            $expectedItem = $this->completeContent($item);
                            $item = $this->getById($expectedItem['id'], $responseData['hydra:member']);
                            $this->assertEquals($expectedItem, $item);
                        }
                    } else {
                        $this->assertJsonContains(['@context' => "/contexts/$shortName", '@type' => $shortName]);
                    }
                }
            )
        );
    }

    protected function completeContent(array $data): array
    {
        $sourceFieldId = $data['sourceField'];
        $categoryId = $data['category'] ?? 0;
        unset($data['sourceField']);
        unset($data['category']);
        $id = implode('-', [$sourceFieldId, $categoryId]);

        $baseData = [
            '@id' => "/facet_configurations/$id",
            '@type' => 'FacetConfiguration',
            'id' => $id,
            'sourceField' => "/source_fields/$sourceFieldId",
            'displayMode' => 'auto',
            'coverageRate' => 90,
            'maxSize' => 10,
            'sortOrder' => BucketInterface::SORT_ORDER_COUNT,
            'isRecommendable' => false,
            'isVirtual' => false,
            'defaultDisplayMode' => 'auto',
            'defaultMaxSize' => 10,
            'defaultCoverageRate' => 90,
            'defaultSortOrder' => BucketInterface::SORT_ORDER_COUNT,
            'defaultIsRecommendable' => false,
            'defaultIsVirtual' => false,
            'defaultPosition' => null,
            'position' => null,
            'category' => $categoryId ? "/categories/$categoryId" : null,
        ];

        if ($categoryId) {
            $baseData['category'] = "/categories/$categoryId";
        }

        return array_merge($baseData, $data);
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
