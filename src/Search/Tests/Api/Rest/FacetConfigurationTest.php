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

namespace Gally\Search\Tests\Api\Rest;

use Gally\Search\Elasticsearch\Request\BucketInterface;
use Gally\Test\AbstractTestCase;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestToTest;
use Gally\User\Constant\Role;
use Gally\User\Entity\User;
use Symfony\Contracts\HttpClient\ResponseInterface;

class FacetConfigurationTest extends AbstractTestCase
{
    protected const PRODUCT_BRAND_SOURCE_FIELD_ID = 900041;
    private const PRODUCT_COLOR_SOURCE_FIELD_ID = 900042;
    private const PRODUCT_CATEGORY_SOURCE_FIELD_ID = 900043;
    private const PRODUCT_LENGTH_SOURCE_FIELD_ID = 900044;
    private const PRODUCT_SIZE_SOURCE_FIELD_ID = 900045;
    private const PRODUCT_WEIGHT_SOURCE_FIELD_ID = 900046;
    private const PRODUCT_IS_ECO_FRIENDLY_SOURCE_FIELD_ID = 900047;
    private const PRODUCT_CREATED_AT_SOURCE_FIELD_ID = 900048;
    private const PRODUCT_COLOR_FULL_SOURCE_FIELD_ID = 900049;
    private const PRODUCT_MANUFACTURE_LOCATION_SOURCE_FIELD_ID = 900050;
    private const PRODUCT_TAGS_SOURCE_FIELD_ID = 900051;

    private const CATEGORY_NAME_SOURCE_FIELD_ID = 65;

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
        return 'facet_configurations';
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
                    ['sourceField' => self::CATEGORY_NAME_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Name', 'sourceFieldCode' => 'name'],
                    ['sourceField' => self::PRODUCT_BRAND_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Brand', 'sourceFieldCode' => 'brand'],
                    ['sourceField' => self::PRODUCT_COLOR_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Color', 'sourceFieldCode' => 'color'],
                    ['sourceField' => self::PRODUCT_CATEGORY_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Category', 'sourceFieldCode' => 'category'],
                    ['sourceField' => self::PRODUCT_LENGTH_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Length', 'sourceFieldCode' => 'length'],
                    ['sourceField' => self::PRODUCT_SIZE_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Size', 'sourceFieldCode' => 'size'],
                    ['sourceField' => self::PRODUCT_WEIGHT_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Weight', 'sourceFieldCode' => 'weight'],
                    ['sourceField' => self::PRODUCT_IS_ECO_FRIENDLY_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Is_eco_friendly', 'sourceFieldCode' => 'is_eco_friendly'],
                    ['sourceField' => self::PRODUCT_CREATED_AT_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Created_at', 'sourceFieldCode' => 'created_at'],
                    ['sourceField' => self::PRODUCT_COLOR_FULL_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Color_full', 'sourceFieldCode' => 'color_full'],
                    ['sourceField' => self::PRODUCT_MANUFACTURE_LOCATION_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Manufacture_location', 'sourceFieldCode' => 'manufacture_location'],
                    ['sourceField' => self::PRODUCT_TAGS_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Tags', 'sourceFieldCode' => 'tags'],
                ],
                200,
            ],
            [
                $user,
                null,
                'cat_1',
                [
                    ['sourceField' => self::CATEGORY_NAME_SOURCE_FIELD_ID, 'category' => 'cat_1', 'sourceFieldLabel' => 'Name', 'sourceFieldCode' => 'name'],
                    ['sourceField' => self::PRODUCT_BRAND_SOURCE_FIELD_ID, 'category' => 'cat_1', 'sourceFieldLabel' => 'Brand', 'sourceFieldCode' => 'brand'],
                    ['sourceField' => self::PRODUCT_COLOR_SOURCE_FIELD_ID, 'category' => 'cat_1', 'sourceFieldLabel' => 'Color', 'sourceFieldCode' => 'color'],
                    ['sourceField' => self::PRODUCT_CATEGORY_SOURCE_FIELD_ID, 'category' => 'cat_1', 'sourceFieldLabel' => 'Category', 'sourceFieldCode' => 'category'],
                    ['sourceField' => self::PRODUCT_LENGTH_SOURCE_FIELD_ID, 'category' => 'cat_1', 'sourceFieldLabel' => 'Length', 'sourceFieldCode' => 'length'],
                    ['sourceField' => self::PRODUCT_SIZE_SOURCE_FIELD_ID, 'category' => 'cat_1', 'sourceFieldLabel' => 'Size', 'sourceFieldCode' => 'size'],
                    ['sourceField' => self::PRODUCT_WEIGHT_SOURCE_FIELD_ID, 'category' => 'cat_1', 'sourceFieldLabel' => 'Weight', 'sourceFieldCode' => 'weight'],
                    ['sourceField' => self::PRODUCT_IS_ECO_FRIENDLY_SOURCE_FIELD_ID, 'category' => 'cat_1', 'sourceFieldLabel' => 'Is_eco_friendly', 'sourceFieldCode' => 'is_eco_friendly'],
                    ['sourceField' => self::PRODUCT_CREATED_AT_SOURCE_FIELD_ID, 'category' => 'cat_1', 'sourceFieldLabel' => 'Created_at', 'sourceFieldCode' => 'created_at'],
                    ['sourceField' => self::PRODUCT_COLOR_FULL_SOURCE_FIELD_ID, 'category' => 'cat_1', 'sourceFieldLabel' => 'Color_full', 'sourceFieldCode' => 'color_full'],
                    ['sourceField' => self::PRODUCT_MANUFACTURE_LOCATION_SOURCE_FIELD_ID, 'category' => 'cat_1', 'sourceFieldLabel' => 'Manufacture_location', 'sourceFieldCode' => 'manufacture_location'],
                    ['sourceField' => self::PRODUCT_TAGS_SOURCE_FIELD_ID, 'category' => 'cat_1', 'sourceFieldLabel' => 'Tags', 'sourceFieldCode' => 'tags'],
                ],
                200,
            ],
            [
                $user,
                null,
                'cat_2',
                [
                    ['sourceField' => self::CATEGORY_NAME_SOURCE_FIELD_ID, 'category' => 'cat_2', 'sourceFieldLabel' => 'Name', 'sourceFieldCode' => 'name'],
                    ['sourceField' => self::PRODUCT_BRAND_SOURCE_FIELD_ID, 'category' => 'cat_2', 'sourceFieldLabel' => 'Brand', 'sourceFieldCode' => 'brand'],
                    ['sourceField' => self::PRODUCT_COLOR_SOURCE_FIELD_ID, 'category' => 'cat_2', 'sourceFieldLabel' => 'Color', 'sourceFieldCode' => 'color'],
                    ['sourceField' => self::PRODUCT_CATEGORY_SOURCE_FIELD_ID, 'category' => 'cat_2', 'sourceFieldLabel' => 'Category', 'sourceFieldCode' => 'category'],
                    ['sourceField' => self::PRODUCT_LENGTH_SOURCE_FIELD_ID, 'category' => 'cat_2', 'sourceFieldLabel' => 'Length', 'sourceFieldCode' => 'length'],
                    ['sourceField' => self::PRODUCT_SIZE_SOURCE_FIELD_ID, 'category' => 'cat_2', 'sourceFieldLabel' => 'Size', 'sourceFieldCode' => 'size'],
                    ['sourceField' => self::PRODUCT_WEIGHT_SOURCE_FIELD_ID, 'category' => 'cat_2', 'sourceFieldLabel' => 'Weight', 'sourceFieldCode' => 'weight'],
                    ['sourceField' => self::PRODUCT_IS_ECO_FRIENDLY_SOURCE_FIELD_ID, 'category' => 'cat_2', 'sourceFieldLabel' => 'Is_eco_friendly', 'sourceFieldCode' => 'is_eco_friendly'],
                    ['sourceField' => self::PRODUCT_CREATED_AT_SOURCE_FIELD_ID, 'category' => 'cat_2', 'sourceFieldLabel' => 'Created_at', 'sourceFieldCode' => 'created_at'],
                    ['sourceField' => self::PRODUCT_COLOR_FULL_SOURCE_FIELD_ID, 'category' => 'cat_2', 'sourceFieldLabel' => 'Color_full', 'sourceFieldCode' => 'color_full'],
                    ['sourceField' => self::PRODUCT_MANUFACTURE_LOCATION_SOURCE_FIELD_ID, 'category' => 'cat_2', 'sourceFieldLabel' => 'Manufacture_location', 'sourceFieldCode' => 'manufacture_location'],
                    ['sourceField' => self::PRODUCT_TAGS_SOURCE_FIELD_ID, 'category' => 'cat_2', 'sourceFieldLabel' => 'Tags', 'sourceFieldCode' => 'tags'],
                ],
                200,
            ],
            [
                $user,
                null,
                'cat-6',
                [
                    ['sourceField' => self::CATEGORY_NAME_SOURCE_FIELD_ID, 'category' => 'cat-6', 'sourceFieldLabel' => 'Name', 'sourceFieldCode' => 'name'],
                    ['sourceField' => self::PRODUCT_BRAND_SOURCE_FIELD_ID, 'category' => 'cat-6', 'sourceFieldLabel' => 'Brand', 'sourceFieldCode' => 'brand'],
                    ['sourceField' => self::PRODUCT_COLOR_SOURCE_FIELD_ID, 'category' => 'cat-6', 'sourceFieldLabel' => 'Color', 'sourceFieldCode' => 'color'],
                    ['sourceField' => self::PRODUCT_CATEGORY_SOURCE_FIELD_ID, 'category' => 'cat-6', 'sourceFieldLabel' => 'Category', 'sourceFieldCode' => 'category'],
                    ['sourceField' => self::PRODUCT_LENGTH_SOURCE_FIELD_ID, 'category' => 'cat-6', 'sourceFieldLabel' => 'Length', 'sourceFieldCode' => 'length'],
                    ['sourceField' => self::PRODUCT_SIZE_SOURCE_FIELD_ID, 'category' => 'cat-6', 'sourceFieldLabel' => 'Size', 'sourceFieldCode' => 'size'],
                    ['sourceField' => self::PRODUCT_WEIGHT_SOURCE_FIELD_ID, 'category' => 'cat-6', 'sourceFieldLabel' => 'Weight', 'sourceFieldCode' => 'weight'],
                    ['sourceField' => self::PRODUCT_IS_ECO_FRIENDLY_SOURCE_FIELD_ID, 'category' => 'cat-6', 'sourceFieldLabel' => 'Is_eco_friendly', 'sourceFieldCode' => 'is_eco_friendly'],
                    ['sourceField' => self::PRODUCT_CREATED_AT_SOURCE_FIELD_ID, 'category' => 'cat-6', 'sourceFieldLabel' => 'Created_at', 'sourceFieldCode' => 'created_at'],
                    ['sourceField' => self::PRODUCT_COLOR_FULL_SOURCE_FIELD_ID, 'category' => 'cat-6', 'sourceFieldLabel' => 'Color_full', 'sourceFieldCode' => 'color_full'],
                    ['sourceField' => self::PRODUCT_MANUFACTURE_LOCATION_SOURCE_FIELD_ID, 'category' => 'cat-6', 'sourceFieldLabel' => 'Manufacture_location', 'sourceFieldCode' => 'manufacture_location'],
                    ['sourceField' => self::PRODUCT_TAGS_SOURCE_FIELD_ID, 'category' => 'cat-6', 'sourceFieldLabel' => 'Tags', 'sourceFieldCode' => 'tags'],
                ],
                200,
            ],
            [
                $user,
                'product',
                null,
                [
                    ['sourceField' => self::PRODUCT_BRAND_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Brand', 'sourceFieldCode' => 'brand'],
                    ['sourceField' => self::PRODUCT_COLOR_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Color', 'sourceFieldCode' => 'color'],
                    ['sourceField' => self::PRODUCT_CATEGORY_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Category', 'sourceFieldCode' => 'category'],
                    ['sourceField' => self::PRODUCT_LENGTH_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Length', 'sourceFieldCode' => 'length'],
                    ['sourceField' => self::PRODUCT_SIZE_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Size', 'sourceFieldCode' => 'size'],
                    ['sourceField' => self::PRODUCT_WEIGHT_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Weight', 'sourceFieldCode' => 'weight'],
                    ['sourceField' => self::PRODUCT_IS_ECO_FRIENDLY_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Is_eco_friendly', 'sourceFieldCode' => 'is_eco_friendly'],
                    ['sourceField' => self::PRODUCT_CREATED_AT_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Created_at', 'sourceFieldCode' => 'created_at'],
                    ['sourceField' => self::PRODUCT_COLOR_FULL_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Color_full', 'sourceFieldCode' => 'color_full'],
                    ['sourceField' => self::PRODUCT_MANUFACTURE_LOCATION_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Manufacture_location', 'sourceFieldCode' => 'manufacture_location'],
                    ['sourceField' => self::PRODUCT_TAGS_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Tags', 'sourceFieldCode' => 'tags'],
                ],
                200,
            ],
            [
                $user,
                'category',
                null,
                [
                    ['sourceField' => self::CATEGORY_NAME_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Name', 'sourceFieldCode' => 'name'],
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
        $updateData = array_merge(
            ['sourceField' => $this->getUri('source_fields', explode('-', $id)[0])],
            $newData,
        );
        $this->validateApiCall(
            new RequestToTest('PUT', "{$this->getApiPath()}/$id", $user, $updateData, ['Content-Type' => 'application/ld+json']),
            new ExpectedResponse(405, null, $expectedMessage)
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
            [null, self::PRODUCT_BRAND_SOURCE_FIELD_ID . '-0', ['coverageRate' => 0], 401, 'Access Denied.'],
            [$admin, self::PRODUCT_BRAND_SOURCE_FIELD_ID . '-0', ['coverageRate' => 0, 'sortOrder' => 'invalidSortOrder'], 422, 'sortOrder: The value you selected is not a valid choice.'],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), self::PRODUCT_BRAND_SOURCE_FIELD_ID . '-0', ['coverageRate' => 0, 'sortOrder' => BucketInterface::SORT_ORDER_COUNT], 200],
            [$admin, self::PRODUCT_BRAND_SOURCE_FIELD_ID . '-0', ['coverageRate' => 1, 'maxSize' => 100, 'sortOrder' => BucketInterface::SORT_ORDER_TERM, 'position' => 1], 200],
            [$admin, self::PRODUCT_BRAND_SOURCE_FIELD_ID . '-cat_1', ['coverageRate' => 10, 'sortOrder' => BucketInterface::SORT_ORDER_MANUAL], 200],
            [$admin, self::PRODUCT_COLOR_SOURCE_FIELD_ID . '-cat_1', ['coverageRate' => 10, 'sortOrder' => BucketInterface::SORT_ORDER_MANUAL, 'position' => 1], 200],
            [$admin, self::PRODUCT_BRAND_SOURCE_FIELD_ID . '-cat_2', ['coverageRate' => 90], 200], // Put the default value back on a sub level
            [$admin, self::PRODUCT_BRAND_SOURCE_FIELD_ID . '-cat-6', ['coverageRate' => 90], 200], // Test with category id with a hyphen
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
                    ['sourceField' => self::CATEGORY_NAME_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Name', 'sourceFieldCode' => 'name'],
                    ['sourceField' => self::PRODUCT_BRAND_SOURCE_FIELD_ID, 'coverageRate' => 1, 'sourceFieldLabel' => 'Brand', 'sourceFieldCode' => 'brand', 'maxSize' => 100, 'sortOrder' => BucketInterface::SORT_ORDER_TERM, 'position' => 1], // product_brand.
                    ['sourceField' => self::PRODUCT_COLOR_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Color', 'sourceFieldCode' => 'color'], // product_color.
                    ['sourceField' => self::PRODUCT_CATEGORY_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Category', 'sourceFieldCode' => 'category'], // product_category.
                    ['sourceField' => self::PRODUCT_LENGTH_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Length', 'sourceFieldCode' => 'length'], // product_length.
                    ['sourceField' => self::PRODUCT_SIZE_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Size', 'sourceFieldCode' => 'size'], // size.
                    ['sourceField' => self::PRODUCT_WEIGHT_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Weight', 'sourceFieldCode' => 'weight'], // weight.
                    ['sourceField' => self::PRODUCT_IS_ECO_FRIENDLY_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Is_eco_friendly', 'sourceFieldCode' => 'is_eco_friendly'],
                    ['sourceField' => self::PRODUCT_CREATED_AT_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Created_at', 'sourceFieldCode' => 'created_at'],
                    ['sourceField' => self::PRODUCT_COLOR_FULL_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Color_full', 'sourceFieldCode' => 'color_full'],
                    ['sourceField' => self::PRODUCT_MANUFACTURE_LOCATION_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Manufacture_location', 'sourceFieldCode' => 'manufacture_location'],
                    ['sourceField' => self::PRODUCT_TAGS_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Tags', 'sourceFieldCode' => 'tags'],
                ],
                200,
            ],
            [
                $user,
                null,
                'cat_1',
                [
                    ['sourceField' => self::CATEGORY_NAME_SOURCE_FIELD_ID, 'category' => 'cat_1', 'sourceFieldLabel' => 'Name', 'sourceFieldCode' => 'name'],
                    ['sourceField' => self::PRODUCT_BRAND_SOURCE_FIELD_ID, 'category' => 'cat_1', 'coverageRate' => 10, 'maxSize' => 100, 'defaultCoverageRate' => 1, 'defaultMaxSize' => 100, 'sourceFieldLabel' => 'Brand', 'sourceFieldCode' => 'brand', 'sortOrder' => BucketInterface::SORT_ORDER_MANUAL, 'defaultSortOrder' => BucketInterface::SORT_ORDER_TERM, 'position' => 1, 'defaultPosition' => 1],
                    ['sourceField' => self::PRODUCT_COLOR_SOURCE_FIELD_ID, 'category' => 'cat_1', 'coverageRate' => 10, 'sourceFieldLabel' => 'Color', 'sourceFieldCode' => 'color', 'sortOrder' => BucketInterface::SORT_ORDER_MANUAL, 'position' => 1],
                    ['sourceField' => self::PRODUCT_CATEGORY_SOURCE_FIELD_ID, 'category' => 'cat_1', 'sourceFieldLabel' => 'Category', 'sourceFieldCode' => 'category'], // product_category.
                    ['sourceField' => self::PRODUCT_LENGTH_SOURCE_FIELD_ID, 'category' => 'cat_1', 'sourceFieldLabel' => 'Length', 'sourceFieldCode' => 'length'], // product_length.
                    ['sourceField' => self::PRODUCT_SIZE_SOURCE_FIELD_ID, 'category' => 'cat_1', 'sourceFieldLabel' => 'Size', 'sourceFieldCode' => 'size'], // size.
                    ['sourceField' => self::PRODUCT_WEIGHT_SOURCE_FIELD_ID, 'category' => 'cat_1', 'sourceFieldLabel' => 'Weight', 'sourceFieldCode' => 'weight'], // weight.
                    ['sourceField' => self::PRODUCT_IS_ECO_FRIENDLY_SOURCE_FIELD_ID, 'category' => 'cat_1', 'sourceFieldLabel' => 'Is_eco_friendly', 'sourceFieldCode' => 'is_eco_friendly'],
                    ['sourceField' => self::PRODUCT_CREATED_AT_SOURCE_FIELD_ID, 'category' => 'cat_1', 'sourceFieldLabel' => 'Created_at', 'sourceFieldCode' => 'created_at'],
                    ['sourceField' => self::PRODUCT_COLOR_FULL_SOURCE_FIELD_ID, 'category' => 'cat_1', 'sourceFieldLabel' => 'Color_full', 'sourceFieldCode' => 'color_full'],
                    ['sourceField' => self::PRODUCT_MANUFACTURE_LOCATION_SOURCE_FIELD_ID, 'category' => 'cat_1', 'sourceFieldLabel' => 'Manufacture_location', 'sourceFieldCode' => 'manufacture_location'],
                    ['sourceField' => self::PRODUCT_TAGS_SOURCE_FIELD_ID, 'category' => 'cat_1', 'sourceFieldLabel' => 'Tags', 'sourceFieldCode' => 'tags'],
                ],
                200,
            ],
            [
                $user,
                null,
                'cat_2',
                [
                    ['sourceField' => self::CATEGORY_NAME_SOURCE_FIELD_ID, 'category' => 'cat_2', 'sourceFieldLabel' => 'Name', 'sourceFieldCode' => 'name'],
                    ['sourceField' => self::PRODUCT_BRAND_SOURCE_FIELD_ID, 'category' => 'cat_2', 'coverageRate' => 90, 'maxSize' => 100, 'defaultCoverageRate' => 1,  'defaultMaxSize' => 100, 'sourceFieldLabel' => 'Brand', 'sourceFieldCode' => 'brand', 'sortOrder' => BucketInterface::SORT_ORDER_TERM, 'defaultSortOrder' => BucketInterface::SORT_ORDER_TERM, 'position' => 1, 'defaultPosition' => 1],
                    ['sourceField' => self::PRODUCT_COLOR_SOURCE_FIELD_ID, 'category' => 'cat_2', 'coverageRate' => 90, 'sourceFieldLabel' => 'Color', 'sourceFieldCode' => 'color'], // product_color.
                    ['sourceField' => self::PRODUCT_CATEGORY_SOURCE_FIELD_ID, 'category' => 'cat_2', 'coverageRate' => 90, 'sourceFieldLabel' => 'Category', 'sourceFieldCode' => 'category'], // product_category.
                    ['sourceField' => self::PRODUCT_LENGTH_SOURCE_FIELD_ID, 'category' => 'cat_2', 'coverageRate' => 90, 'sourceFieldLabel' => 'Length', 'sourceFieldCode' => 'length'], // product_length.
                    ['sourceField' => self::PRODUCT_SIZE_SOURCE_FIELD_ID, 'category' => 'cat_2', 'coverageRate' => 90, 'sourceFieldLabel' => 'Size', 'sourceFieldCode' => 'size'], // size.
                    ['sourceField' => self::PRODUCT_WEIGHT_SOURCE_FIELD_ID, 'category' => 'cat_2', 'coverageRate' => 90, 'sourceFieldLabel' => 'Weight', 'sourceFieldCode' => 'weight'], // weight.
                    ['sourceField' => self::PRODUCT_IS_ECO_FRIENDLY_SOURCE_FIELD_ID, 'category' => 'cat_2', 'coverageRate' => 90, 'sourceFieldLabel' => 'Is_eco_friendly', 'sourceFieldCode' => 'is_eco_friendly'],
                    ['sourceField' => self::PRODUCT_CREATED_AT_SOURCE_FIELD_ID, 'category' => 'cat_2', 'coverageRate' => 90, 'sourceFieldLabel' => 'Created_at', 'sourceFieldCode' => 'created_at'],
                    ['sourceField' => self::PRODUCT_COLOR_FULL_SOURCE_FIELD_ID, 'category' => 'cat_2', 'coverageRate' => 90, 'sourceFieldLabel' => 'Color_full', 'sourceFieldCode' => 'color_full'],
                    ['sourceField' => self::PRODUCT_MANUFACTURE_LOCATION_SOURCE_FIELD_ID, 'category' => 'cat_2', 'coverageRate' => 90, 'sourceFieldLabel' => 'Manufacture_location', 'sourceFieldCode' => 'manufacture_location'],
                    ['sourceField' => self::PRODUCT_TAGS_SOURCE_FIELD_ID, 'category' => 'cat_2', 'coverageRate' => 90, 'sourceFieldLabel' => 'Tags', 'sourceFieldCode' => 'tags'],
                ],
                200,
            ],
            [
                $user,
                null,
                'cat-6',
                [
                    ['sourceField' => self::CATEGORY_NAME_SOURCE_FIELD_ID, 'category' => 'cat-6', 'sourceFieldLabel' => 'Name', 'sourceFieldCode' => 'name'],
                    ['sourceField' => self::PRODUCT_BRAND_SOURCE_FIELD_ID, 'category' => 'cat-6', 'coverageRate' => 90, 'maxSize' => 100, 'defaultCoverageRate' => 1,  'defaultMaxSize' => 100, 'sourceFieldLabel' => 'Brand', 'sortOrder' => BucketInterface::SORT_ORDER_TERM, 'defaultSortOrder' => BucketInterface::SORT_ORDER_TERM, 'position' => 1, 'defaultPosition' => 1, 'sourceFieldCode' => 'brand'],
                    ['sourceField' => self::PRODUCT_COLOR_SOURCE_FIELD_ID, 'category' => 'cat-6', 'coverageRate' => 90, 'sourceFieldLabel' => 'Color', 'sourceFieldCode' => 'color'],
                    ['sourceField' => self::PRODUCT_CATEGORY_SOURCE_FIELD_ID, 'category' => 'cat-6', 'coverageRate' => 90, 'sourceFieldLabel' => 'Category', 'sourceFieldCode' => 'category'],
                    ['sourceField' => self::PRODUCT_LENGTH_SOURCE_FIELD_ID, 'category' => 'cat-6', 'coverageRate' => 90, 'sourceFieldLabel' => 'Length', 'sourceFieldCode' => 'length'],
                    ['sourceField' => self::PRODUCT_SIZE_SOURCE_FIELD_ID, 'category' => 'cat-6', 'coverageRate' => 90, 'sourceFieldLabel' => 'Size', 'sourceFieldCode' => 'size'],
                    ['sourceField' => self::PRODUCT_WEIGHT_SOURCE_FIELD_ID, 'category' => 'cat-6', 'coverageRate' => 90, 'sourceFieldLabel' => 'Weight', 'sourceFieldCode' => 'weight'],
                    ['sourceField' => self::PRODUCT_IS_ECO_FRIENDLY_SOURCE_FIELD_ID, 'category' => 'cat-6', 'coverageRate' => 90, 'sourceFieldLabel' => 'Is_eco_friendly', 'sourceFieldCode' => 'is_eco_friendly'],
                    ['sourceField' => self::PRODUCT_CREATED_AT_SOURCE_FIELD_ID, 'category' => 'cat-6', 'coverageRate' => 90, 'sourceFieldLabel' => 'Created_at', 'sourceFieldCode' => 'created_at'],
                    ['sourceField' => self::PRODUCT_COLOR_FULL_SOURCE_FIELD_ID, 'category' => 'cat-6', 'coverageRate' => 90, 'sourceFieldLabel' => 'Color_full', 'sourceFieldCode' => 'color_full'],
                    ['sourceField' => self::PRODUCT_MANUFACTURE_LOCATION_SOURCE_FIELD_ID, 'category' => 'cat-6', 'coverageRate' => 90, 'sourceFieldLabel' => 'Manufacture_location', 'sourceFieldCode' => 'manufacture_location'],
                    ['sourceField' => self::PRODUCT_TAGS_SOURCE_FIELD_ID, 'category' => 'cat-6', 'coverageRate' => 90, 'sourceFieldLabel' => 'Tags', 'sourceFieldCode' => 'tags'],
                ],
                200,
            ],
            [
                $user,
                'product',
                null,
                [
                    ['sourceField' => self::PRODUCT_BRAND_SOURCE_FIELD_ID, 'coverageRate' => 1, 'sourceFieldLabel' => 'Brand', 'sourceFieldCode' => 'brand', 'maxSize' => 100, 'sortOrder' => BucketInterface::SORT_ORDER_TERM, 'position' => 1], // product_brand.
                    ['sourceField' => self::PRODUCT_COLOR_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Color', 'sourceFieldCode' => 'color'], // product_color.
                    ['sourceField' => self::PRODUCT_CATEGORY_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Category', 'sourceFieldCode' => 'category'], // product_category.
                    ['sourceField' => self::PRODUCT_LENGTH_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Length', 'sourceFieldCode' => 'length'], // product_length.
                    ['sourceField' => self::PRODUCT_SIZE_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Size', 'sourceFieldCode' => 'size'], // size.
                    ['sourceField' => self::PRODUCT_WEIGHT_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Weight', 'sourceFieldCode' => 'weight'], // weight.
                    ['sourceField' => self::PRODUCT_IS_ECO_FRIENDLY_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Is_eco_friendly', 'sourceFieldCode' => 'is_eco_friendly'],
                    ['sourceField' => self::PRODUCT_CREATED_AT_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Created_at', 'sourceFieldCode' => 'created_at'],
                    ['sourceField' => self::PRODUCT_COLOR_FULL_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Color_full', 'sourceFieldCode' => 'color_full'],
                    ['sourceField' => self::PRODUCT_MANUFACTURE_LOCATION_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Manufacture_location', 'sourceFieldCode' => 'manufacture_location'],
                    ['sourceField' => self::PRODUCT_TAGS_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Tags', 'sourceFieldCode' => 'tags'],
                ],
                200,
            ],
            [
                null,
                'category',
                null,
                [
                    ['sourceField' => self::CATEGORY_NAME_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Name', 'sourceFieldCode' => 'name'],
                ],
                401,
                'Access Denied.',
            ],
            [
                $user,
                'category',
                null,
                [
                    ['sourceField' => self::CATEGORY_NAME_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Name', 'sourceFieldCode' => 'name'],
                ],
                200,
            ],
            [
                $this->getUser(Role::ROLE_ADMIN),
                'category',
                null,
                [
                    ['sourceField' => self::CATEGORY_NAME_SOURCE_FIELD_ID, 'sourceFieldLabel' => 'Name', 'sourceFieldCode' => 'name'],
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
                                    '@context' => $this->getRoute("contexts/$shortName"),
                                    '@type' => $shortName,
                                    '@id' => $this->getUri('facet_configurations', $expectedData['id']),
                                ],
                                $expectedData
                            )
                        );
                    } else {
                        $this->assertJsonContains(['@context' => $this->getRoute("contexts/$shortName"), '@type' => $shortName]);
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
            [null, self::PRODUCT_BRAND_SOURCE_FIELD_ID . '-0', ['id' => self::PRODUCT_BRAND_SOURCE_FIELD_ID . '-0'], 401],
            [$user, self::PRODUCT_BRAND_SOURCE_FIELD_ID . '-0', ['id' => self::PRODUCT_BRAND_SOURCE_FIELD_ID . '-0'], 200],
            [$user, self::PRODUCT_BRAND_SOURCE_FIELD_ID . '-cat-6', ['id' => self::PRODUCT_BRAND_SOURCE_FIELD_ID . '-cat-6'], 200],
            [$this->getUser(Role::ROLE_ADMIN), self::PRODUCT_BRAND_SOURCE_FIELD_ID . '-0', ['id' => self::PRODUCT_BRAND_SOURCE_FIELD_ID . '-0'], 200],
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
            [null, self::PRODUCT_BRAND_SOURCE_FIELD_ID . '-0', 401, 'Access Denied.'],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), self::PRODUCT_BRAND_SOURCE_FIELD_ID . '-0', 204],
            [$this->getUser(Role::ROLE_ADMIN), self::PRODUCT_BRAND_SOURCE_FIELD_ID . '-cat_1', 204],
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
                                '@context' => $this->getRoute("contexts/$shortName"),
                                '@id' => $this->getRoute('facet_configurations'),
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
                        $this->assertJsonContains(['@context' => $this->getRoute("contexts/$shortName"), '@type' => $shortName]);
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
            '@id' => $this->getUri('facet_configurations', $id),
            '@type' => 'FacetConfiguration',
            'id' => $id,
            'sourceField' => $this->getUri('source_fields', $sourceFieldId),
            'displayMode' => 'auto',
            'coverageRate' => 90,
            'maxSize' => 10,
            'sortOrder' => BucketInterface::SORT_ORDER_COUNT,
            'isRecommendable' => false,
            'isVirtual' => false,
            'booleanLogic' => 'OR',
            'defaultDisplayMode' => 'auto',
            'defaultMaxSize' => 10,
            'defaultCoverageRate' => 90,
            'defaultSortOrder' => BucketInterface::SORT_ORDER_COUNT,
            'defaultIsRecommendable' => false,
            'defaultIsVirtual' => false,
            'defaultBooleanLogic' => 'OR',
            'defaultPosition' => null,
            'position' => null,
            'category' => $categoryId ? $this->getUri('categories', $categoryId) : null,
        ];

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
