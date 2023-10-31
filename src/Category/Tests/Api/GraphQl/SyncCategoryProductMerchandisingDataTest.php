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

namespace Gally\Category\Tests\Api\GraphQl;

use OpenSearch\Client;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Category\Repository\CategoryProductMerchandisingRepository;
use Gally\Category\Tests\Api\CategoryTestTrait;
use Gally\Fixture\Service\ElasticsearchFixturesInterface;
use Gally\Index\Service\IndexSettings;
use Gally\Test\AbstractTest;

class SyncCategoryProductMerchandisingDataTest extends AbstractTest
{
    use CategoryTestTrait;
    use IndexActions;

    protected static Client $client;
    protected static IndexSettings $indexSettings;
    protected static array $subCategoryFields;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$client = static::getContainer()->get('OpenSearch\Client');
        self::$indexSettings = static::getContainer()->get(IndexSettings::class);
        self::$subCategoryFields = ['position'];

        self::loadFixture([
            __DIR__ . '/../../fixtures/catalogs.yaml',
            __DIR__ . '/../../fixtures/categories.yaml',
            __DIR__ . '/../../fixtures/source_field.yaml',
            __DIR__ . '/../../fixtures/metadata.yaml',
            __DIR__ . '/../../fixtures/configurations.yaml',
            __DIR__ . '/../../fixtures/product_merchandising_bulk.yaml',
        ]);
        self::createEntityElasticsearchIndices('product');
        self::loadElasticsearchDocumentFixtures([__DIR__ . '/../../fixtures/product_documents_bulk.json']);
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        self::deleteEntityElasticsearchIndices('product');
    }

    /**
     * @dataProvider indexDataProvider
     */
    public function testInstallIndex(string $indexName, array $expectedPositions)
    {
        $indexNameTest = ElasticsearchFixturesInterface::PREFIX_TEST_INDEX . $indexName;
        $this->installIndex($indexNameTest);
        $this->checkPositionDataFromEs($expectedPositions);
    }

    /**
     * @depends testInstallIndex
     */
    public function testDeleteIndex()
    {
        $localizedCatalogB2cFr = static::getContainer()->get(LocalizedCatalogRepository::class)->find(1);
        $productIds = ['p_1', 'p_2', 'p_3', 'p_4'];
        $categoryProductMerchandisingRepository = static::getContainer()->get(CategoryProductMerchandisingRepository::class);

        $positions = $categoryProductMerchandisingRepository->findBy(['productId' => $productIds, 'localizedCatalog' => $localizedCatalogB2cFr]);
        $this->assertGreaterThan(0, \count($positions));

        $indexNameTest = ElasticsearchFixturesInterface::PREFIX_TEST_INDEX . 'gally_b2c_fr_product';
        $this->bulkDeleteIndex($indexNameTest, $productIds);
        $positions = $categoryProductMerchandisingRepository->findBy(['productId' => $productIds, 'localizedCatalog' => $localizedCatalogB2cFr]);
        $this->assertCount(0, $positions);

        // Reset fixtures data.
        self::loadFixture([
            __DIR__ . '/../../fixtures/catalogs.yaml',
            __DIR__ . '/../../fixtures/categories.yaml',
            __DIR__ . '/../../fixtures/source_field.yaml',
            __DIR__ . '/../../fixtures/metadata.yaml',
            __DIR__ . '/../../fixtures/configurations.yaml',
            __DIR__ . '/../../fixtures/product_merchandising_bulk.yaml',
        ]);
        self::deleteElasticsearchFixtures();
        self::createEntityElasticsearchIndices('product');
    }

    /**
     * @dataProvider indexDataProvider
     * @depends testDeleteIndex
     */
    public function testBulkIndex(string $indexName, array $expectedPositions)
    {
        $documentsFile = __DIR__ . '/../../fixtures/product_documents_bulk.json';
        $indices = file_get_contents(__DIR__ . '/../../fixtures/product_documents_bulk.json');
        $indices = json_decode($indices, true);
        $this->validateBulkIndexData($indices, $documentsFile, $indexName, $expectedPositions);
    }

    protected function validateBulkIndexData(array $indices, string $documentsFile, string $indexName, array $expectedPositions)
    {
        $indexFound = false;
        foreach ($indices as $index) {
            if ($index['index_name'] === $indexName) {
                $indexNameTest = ElasticsearchFixturesInterface::PREFIX_TEST_INDEX . $index['index_name'];
                $this->bulkIndex($indexNameTest, $index['documents']);
                $this->checkPositionDataFromEs($expectedPositions);
                $indexFound = true;
                break;
            }
        }

        $this->assertTrue($indexFound, "Index '$indexName' not found in the file $documentsFile");
    }

    public function indexDataProvider(): array
    {
        $productId1 = 'p_1';
        $productId2 = 'p_2';
        $productId3 = 'p_3';
        $productId4 = 'p_4';
        $categoryIdOne = 'one';
        $categoryIdTwo = 'two';
        $categoryIdThree = 'three';
        $categoryIdFive = 'five';
        $localizedCatalogIdB2cFr = 1;
        $localizedCatalogIdB2cEn = 2;
        $localizedCatalogIdb2bEn = 3;

        return [
            [
                'gally_b2c_fr_product',
                [
                    $localizedCatalogIdB2cFr => [
                        $productId1 => [$categoryIdOne => ['position' => 1], $categoryIdTwo => ['position' => 2]],
                        $productId2 => [$categoryIdOne => ['position' => 2], $categoryIdTwo => ['position' => 1]],
                        $productId3 => [$categoryIdThree => ['position' => 1]],
                        $productId4 => [],
                    ],
                ],
            ],
            [
                'gally_b2c_en_product',
                [
                    $localizedCatalogIdB2cEn => [
                        $productId1 => [$categoryIdOne => ['position' => 10], $categoryIdTwo => []],
                        $productId2 => [$categoryIdOne => ['position' => 20], $categoryIdTwo => []],
                        $productId3 => [$categoryIdThree => ['position' => 10]],
                        $productId4 => [],
                    ],
                ],
            ],
            [
                'gally_b2b_en_product',
                [
                    $localizedCatalogIdb2bEn => [
                        $productId1 => [$categoryIdOne => [], $categoryIdTwo => []],
                        $productId2 => [$categoryIdOne => [], $categoryIdTwo => []],
                        $productId3 => [],
                        $productId4 => [$categoryIdFive => ['position' => 100]],
                    ],
                ],
            ],
        ];
    }
}
