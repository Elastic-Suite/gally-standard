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

namespace Gally\Index\Tests\Api;

use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Index\Repository\DataStream\DataStreamRepositoryInterface;
use Gally\User\Constant\Role;
use OpenSearch\Client;

trait DataStreamTestTrait
{
    protected static DataStreamRepositoryInterface $dataStreamRepository;
    protected static LocalizedCatalogRepository $catalogRepository;
    protected static Client $client;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::loadFixture([
            __DIR__ . '/../fixtures/catalogs.yaml',
            __DIR__ . '/../fixtures/source_field.yaml',
            __DIR__ . '/../fixtures/metadata.yaml',
        ]);

        self::$dataStreamRepository = static::getContainer()->get(DataStreamRepositoryInterface::class);
        self::$catalogRepository = static::getContainer()->get(LocalizedCatalogRepository::class);
        self::$client = static::getContainer()->get('opensearch.client.test');

        self::cleanupTestDataStreams();
    }

    public static function tearDownAfterClass(): void
    {
        self::cleanupTestDataStreams();
        parent::tearDownAfterClass();
    }

    protected static function cleanupTestDataStreams(): void
    {
        try {
            $response = self::$client->indices()->getDataStream();
            foreach ($response['data_streams'] as $dataStream) {
                if (str_contains($dataStream['name'], 'gally_test_')) {
                    self::$client->indices()->deleteDataStream(['name' => $dataStream['name']]);
                }
            }
        } catch (\Exception $e) {
            // Ignore errors during cleanup
        }
    }

    public function getCollectionDataProvider(): iterable
    {
        yield 'anonymous' => [null, 401];
        yield 'contributor' => [Role::ROLE_CONTRIBUTOR, 200];
        yield 'admin' => [Role::ROLE_ADMIN, 200];
    }

    public function createDataStreamDataProvider(): iterable
    {
        yield 'anonymous' => [null, 'product', 'b2c_en', 401];
        yield 'contributor' => [Role::ROLE_CONTRIBUTOR, 'product', 'b2c_en', 403];

        foreach (self::$catalogRepository->findAll() as $catalog) {
            yield "admin_product_{$catalog->getCode()}" => [
                Role::ROLE_ADMIN,
                'product',
                $catalog->getCode(),
                201,
            ];
            yield "admin_category_{$catalog->getCode()}" => [
                Role::ROLE_ADMIN,
                'category',
                $catalog->getCode(),
                201,
            ];
        }
    }

    public function bulkDataStreamDataProvider(): iterable
    {
        yield 'anonymous' => [null, 401];
        yield 'contributor' => [Role::ROLE_CONTRIBUTOR, 403];
        yield 'admin' => [Role::ROLE_ADMIN, 200];
    }

    public function bulkDeleteDataStreamDataProvider(): iterable
    {
        yield 'anonymous' => [null, 401];
        yield 'contributor' => [Role::ROLE_CONTRIBUTOR, 403];
        yield 'admin' => [Role::ROLE_ADMIN, 200];
    }

    public function deleteDataStreamDataProvider(): iterable
    {
        yield 'anonymous' => [null, 'b2c_en', 401];
        yield 'contributor' => [Role::ROLE_CONTRIBUTOR, 'b2c_en', 403];
        yield 'admin' => [Role::ROLE_ADMIN, 'b2c_en', 204];
    }

    protected function getTestDocuments(): array
    {
        return [
            ['id' => '1', 'name' => 'Product 1', '@timestamp' => date('c')],
            ['id' => '2', 'name' => 'Product 2', '@timestamp' => date('c')],
        ];
    }
}
