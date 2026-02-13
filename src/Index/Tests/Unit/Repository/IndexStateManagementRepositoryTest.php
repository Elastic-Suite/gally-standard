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

namespace Gally\Index\Tests\Unit\Repository;

use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Index\Entity\IndexStateManagement;
use Gally\Index\Repository\IndexStateManagement\IndexStateManagementRepositoryInterface;
use Gally\Metadata\Repository\MetadataRepository;
use Gally\Test\AbstractTestCase;

class IndexStateManagementRepositoryTest extends AbstractTestCase
{
    private static IndexStateManagementRepositoryInterface $repository;
    private static LocalizedCatalogRepository $localizedCatalogRepository;
    private static MetadataRepository $metadataRepository;

    private static array $testPolicies = [
        'gally_test__gally_localized_catalog_b2c_fr_product',
        'gally_test__gally_localized_catalog_b2c_en_tracking_event',
        'gally_test__gally_localized_catalog_b2c_fr_dummy-1',
        'gally_test__gally_localized_catalog_b2c_en_dummy-2',
        'gally_test__gally_localized_catalog_b2c_en_dummy-3',
    ];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        \assert(static::getContainer()->get(IndexStateManagementRepositoryInterface::class) instanceof IndexStateManagementRepositoryInterface);
        self::$repository = static::getContainer()->get(IndexStateManagementRepositoryInterface::class);
        \assert(static::getContainer()->get(LocalizedCatalogRepository::class) instanceof LocalizedCatalogRepository);
        self::$localizedCatalogRepository = static::getContainer()->get(LocalizedCatalogRepository::class);
        \assert(static::getContainer()->get(MetadataRepository::class) instanceof MetadataRepository);
        self::$metadataRepository = static::getContainer()->get(MetadataRepository::class);
        self::cleanupTestPolicies();
    }

    public static function tearDownAfterClass(): void
    {
        self::cleanupTestPolicies();
        parent::tearDownAfterClass();
    }

    /**
     * @dataProvider createForEntityDataProvider
     */
    public function testCreateForEntity(
        string $entityCode,
        string $localizedCatalogCode,
        array $expectedData,
        ?string $expectedExceptionMessage = null,
    ) {
        if ($expectedExceptionMessage) {
            $this->expectExceptionMessage($expectedExceptionMessage);
        }

        $this->validatePolicy(
            $expectedData,
            self::$repository->createForEntity(
                self::$metadataRepository->findByEntity($entityCode),
                self::$localizedCatalogRepository->findOneBy(['code' => $localizedCatalogCode]),
            )
        );
    }

    public function createForEntityDataProvider(): iterable
    {
        yield 'invalid policy' => [
            'fake_entity',
            'b2c_fr',
            [],
            'Entity type [fake_entity] does not exist',
        ];

        yield 'product policy' => [
            'product',
            'b2c_fr',
            ['id' => 'gally_test__gally_localized_catalog_b2c_fr_product', 'name' => 'product', 'description' => '', 'indexPatterns' => ['gally_test__gally_localized_catalog_b2c_fr_product'], 'rolloverAfter' => 30, 'deleteAfter' => 365],
        ];

        yield 'event policy' => [
            'tracking_event',
            'b2c_en',
            ['id' => 'gally_test__gally_localized_catalog_b2c_en_tracking_event', 'name' => 'tracking_event', 'description' => '', 'indexPatterns' => ['gally_test__gally_localized_catalog_b2c_en_tracking_event'], 'rolloverAfter' => 1, 'deleteAfter' => 1],
        ];
    }

    /**
     * @depends testCreateForEntity
     *
     * @dataProvider createDataProvider
     */
    public function testCreate(array $data, string $localizedCatalogCode): void
    {
        $localizedCatalog = self::$localizedCatalogRepository->findOneBy(['code' => $localizedCatalogCode]);

        $this->validatePolicy(
            $data,
            self::$repository->create(
                identifier: $data['name'] ?? null,
                localizedCatalog: $localizedCatalog,
                indexPatterns: $data['indexPatterns'] ?? null,
                priority: $data['priority'] ?? null,
                description: $data['description'] ?? '',
                deleteAfter: $data['deleteAfter'] ?? null,
                rolloverAfter: $data['rolloverAfter'] ?? null,
            )
        );
    }

    public function createDataProvider(): iterable
    {
        yield 'complete policy with all fields' => [
            ['id' => 'gally_test__gally_localized_catalog_b2c_fr_dummy-1', 'name' => 'dummy-1', 'description' => 'Test ism', 'indexPatterns' => ['gally_product_*'], 'deleteAfter' => 365, 'rolloverAfter' => 30],
            'b2c_fr',
        ];

        yield 'policy without description' => [
            ['id' => 'gally_test__gally_localized_catalog_b2c_en_dummy-2', 'name' => 'dummy-2', 'indexPatterns' => ['gally_category_*'], 'deleteAfter' => 180],
            'b2c_en',
        ];

        yield 'policy with rollover' => [
            ['id' => 'gally_test__gally_localized_catalog_b2c_en_dummy-3', 'name' => 'dummy-3', 'description' => 'Policy with rollover', 'indexPatterns' => ['gally_logs_*'], 'rolloverAfter' => 7, 'deleteAfter' => 90],
            'b2c_en',
        ];
    }

    /**
     * @depends testCreate
     *
     * @dataProvider findByMetadataDataProvider
     */
    public function testFindByMetadata(string $entityCode, string $localizedCatalogCode, ?array $expectedData): void
    {
        $foundPolicy = self::$repository->findByMetadata(
            self::$metadataRepository->findByEntity($entityCode),
            self::$localizedCatalogRepository->findOneBy(['code' => $localizedCatalogCode]),
        );

        if (null === $expectedData) {
            $this->assertNull($foundPolicy);

            return;
        }

        $this->assertNotNull($foundPolicy);
        $this->validatePolicy($expectedData, $foundPolicy);
    }

    public function findByMetadataDataProvider(): iterable
    {
        yield 'product policy' => [
            'product',
            'b2c_fr',
            ['id' => 'gally_test__gally_localized_catalog_b2c_fr_product', 'name' => 'product', 'description' => '', 'indexPatterns' => ['gally_test__gally_localized_catalog_b2c_fr_product'], 'rolloverAfter' => 30, 'deleteAfter' => 365],
        ];

        yield 'event policy' => [
            'tracking_event',
            'b2c_en',
            ['id' => 'gally_test__gally_localized_catalog_b2c_en_tracking_event', 'name' => 'tracking_event', 'description' => '', 'indexPatterns' => ['gally_test__gally_localized_catalog_b2c_en_tracking_event'], 'rolloverAfter' => 1, 'deleteAfter' => 1],
        ];

        yield 'missing event policy fr' => [
            'tracking_event',
            'b2c_fr',
            null,
        ];

        yield 'missing category policy' => [
            'category',
            'b2c_fr',
            null,
        ];
    }

    /**
     * @depends testFindByMetadata
     *
     * @dataProvider findByNameDataProvider
     */
    public function testFindByName(string $name, string $localizedCatalogCode, ?array $expectedData): void
    {
        $foundPolicy = self::$repository->findByName(
            $name,
            self::$localizedCatalogRepository->findOneBy(['code' => $localizedCatalogCode]),
        );

        if (null === $expectedData) {
            $this->assertNull($foundPolicy);

            return;
        }

        $this->assertNotNull($foundPolicy);
        $this->validatePolicy($expectedData, $foundPolicy);
    }

    public function findByNameDataProvider(): iterable
    {
        yield 'complete policy with all fields' => [
            'dummy-1',
            'b2c_fr',
            ['id' => 'gally_test__gally_localized_catalog_b2c_fr_dummy-1', 'name' => 'dummy-1', 'description' => 'Test ism', 'indexPatterns' => ['gally_product_*'], 'deleteAfter' => 365, 'rolloverAfter' => 30],
        ];

        yield 'policy without description' => [
            'dummy-2',
            'b2c_en',
            ['id' => 'gally_test__gally_localized_catalog_b2c_en_dummy-2', 'name' => 'dummy-2', 'indexPatterns' => ['gally_category_*'], 'deleteAfter' => 180],
        ];

        yield 'policy with rollover' => [
            'dummy-3',
            'b2c_en',
            ['id' => 'gally_test__gally_localized_catalog_b2c_en_dummy-3', 'name' => 'dummy-3', 'description' => 'Policy with rollover', 'indexPatterns' => ['gally_logs_*'], 'rolloverAfter' => 7, 'deleteAfter' => 90],
        ];

        yield 'non_existent' => [
            'non_existent',
            'b2c_fr',
            null,
        ];
    }

    /**
     * @depends testFindByName
     */
    public function testUpdate(): void
    {
        $policy = self::$repository->findByName(
            'dummy-1',
            self::$localizedCatalogRepository->findOneBy(['code' => 'b2c_fr']),
        );

        $this->assertNotNull($policy);
        $policy->setDescription('Updated description');
        $policy->setRolloverAfter(45);

        $updatedPolicy = self::$repository->update($policy);

        $this->validatePolicy(
            ['id' => 'gally_test__gally_localized_catalog_b2c_fr_dummy-1', 'name' => 'dummy-1', 'description' => 'Updated description', 'indexPatterns' => ['gally_product_*'], 'deleteAfter' => 365, 'rolloverAfter' => 45],
            $updatedPolicy
        );
    }

    /**
     * @depends testUpdate
     */
    public function testDeletePolicy(): void
    {
        self::$repository->delete('gally_test__gally_localized_catalog_b2c_fr_dummy-1');

        $deletedPolicy = self::$repository->findByName(
            'dummy-1',
            self::$localizedCatalogRepository->findOneBy(['code' => 'b2c_fr']),
        );
        $this->assertNull($deletedPolicy);
    }

    /**
     * @depends testDeletePolicy
     */
    public function testFindAll(): void
    {
        $b2cFrCatalog = self::$localizedCatalogRepository->findOneBy(['code' => 'b2c_fr']);
        $b2cEnCatalog = self::$localizedCatalogRepository->findOneBy(['code' => 'b2c_en']);

        $policiesFr = self::$repository->findAll($b2cFrCatalog);
        $policiesEn = self::$repository->findAll($b2cEnCatalog);

        $this->assertGreaterThanOrEqual(1, \count($policiesFr));
        $this->assertGreaterThanOrEqual(2, \count($policiesEn));

        $namesFr = array_map(fn ($p) => $p->getName(), $policiesFr);
        $namesEn = array_map(fn ($p) => $p->getName(), $policiesEn);

        $this->assertContains('product', $namesFr);
        $this->assertContains('tracking_event', $namesEn);
        $this->assertContains('dummy-2', $namesEn);
        $this->assertContains('dummy-3', $namesEn);
    }

    private static function cleanupTestPolicies(): void
    {
        foreach (self::$testPolicies as $policyId) {
            try {
                self::$repository->delete($policyId);
            } catch (\Exception $e) {
                // Policy doesn't exist, ignore
            }
        }
    }

    private function validatePolicy(array $expectedData, IndexStateManagement $policy): void
    {
        $this->assertEquals($expectedData['id'], $policy->getId());
        $this->assertEquals($expectedData['name'], $policy->getName());
        $this->assertEquals($expectedData['indexPatterns'], $policy->getIndexPatterns());
        $this->assertEquals($expectedData['priority'] ?? null, $policy->getPriority());
        $this->assertEquals($expectedData['description'] ?? '', $policy->getDescription());
        $this->assertEquals($expectedData['deleteAfter'] ?? null, $policy->getDeleteAfter());
        $this->assertEquals($expectedData['rolloverAfter'] ?? null, $policy->getRolloverAfter());
        $this->assertNotNull($policy->getSeqNo());
        $this->assertNotNull($policy->getPrimaryTerm());
    }
}
