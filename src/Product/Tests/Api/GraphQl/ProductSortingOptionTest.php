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

namespace Gally\Product\Tests\Api\GraphQl;

use Gally\Test\AbstractTestCase;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestGraphQlToTest;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ProductSortingOptionTest extends AbstractTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::loadFixture([
            __DIR__ . '/../../fixtures/source_field.yaml',
            __DIR__ . '/../../fixtures/metadata.yaml',
        ]);
    }

    public function testGetCollection(): void
    {
        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                      productSortingOptions {
                        code
                        label
                      }
                    }
                GQL,
                null
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) {
                    $responseData = $response->toArray();
                    $this->assertSame(
                        [
                            ['code' => 'my_stock__status', 'label' => 'Stock status'],
                            ['code' => 'name', 'label' => 'Name'],
                            ['code' => 'brand__label', 'label' => 'Brand'],
                            ['code' => 'size', 'label' => 'Size'],
                            ['code' => 'my_price__price', 'label' => 'Price'],
                            ['code' => 'price_as_nested__price', 'label' => 'Price_as_nested.price'],
                            ['code' => 'created_at', 'label' => 'Created_at'],
                            ['code' => 'category__position', 'label' => 'Position'],
                            ['code' => 'manufacture_location', 'label' => 'Manufacture_location\'s distance'],
                            ['code' => '_score', 'label' => 'Relevance'],
                        ],
                        $responseData['data']['productSortingOptions']
                    );
                }
            )
        );
    }
}
