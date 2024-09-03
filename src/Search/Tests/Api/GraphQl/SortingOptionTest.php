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

use Gally\Test\AbstractTestCase;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestGraphQlToTest;
use Symfony\Contracts\HttpClient\ResponseInterface;

class SortingOptionTest extends AbstractTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::loadFixture([
            __DIR__ . '/../../fixtures/source_field_sortable.yaml',
            __DIR__ . '/../../fixtures/metadata.yaml',
        ]);
    }

    public function testGetCollection(): void
    {
        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    {
                      sortingOptions (entityType: "product_document"){
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
                            ['code' => 'name', 'label' => 'Name'],
                            ['code' => 'category__position', 'label' => 'Category.position'],
                            ['code' => 'real_category__position', 'label' => 'Position'],
                            ['code' => 'price__price', 'label' => 'Price'],
                            ['code' => 'stock__status', 'label' => 'Stock status'],
                            ['code' => 'manufacture_location', 'label' => 'Manufacture_location\'s distance'],
                            ['code' => '_score', 'label' => 'Relevance'],
                        ],
                        $responseData['data']['sortingOptions']
                    );
                }
            )
        );
    }
}
