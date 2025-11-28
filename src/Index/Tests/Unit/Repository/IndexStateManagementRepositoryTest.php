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

use Gally\Index\Entity\IndexStateManagement;
use Gally\Index\Repository\IndexStateManagement\IndexStateManagementRepositoryInterface;
use Gally\Test\AbstractTestCase;

class IndexStateManagementRepositoryTest extends AbstractTestCase
{
    private static IndexStateManagementRepositoryInterface $repository;

    private static array $testPolicies = [
        'gally_test__gally_test_policy',
        'gally_test__gally_dummy-1',
        'gally_test__gally_dummy-2',
        'gally_test__gally_no-delete-after',
    ];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        \assert(static::getContainer()->get(IndexStateManagementRepositoryInterface::class) instanceof IndexStateManagementRepositoryInterface);
        self::$repository = static::getContainer()->get(IndexStateManagementRepositoryInterface::class);
        self::cleanupTestPolicies();
    }

    public static function tearDownAfterClass(): void
    {
        self::cleanupTestPolicies();
        parent::tearDownAfterClass();
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(array $data, ?string $expectedException): void
    {
        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $policy = new IndexStateManagement(
            name: $data['name'] ?? null,
            indexPattern: $data['indexPattern'] ?? null,
            priority: $data['priority'] ?? null,
            description: $data['description'] ?? '',
            deleteAfter: $data['deleteAfter'] ?? null,
        );

        $savedPolicy = self::$repository->save($policy);

        $this->assertNotNull($savedPolicy->getId());
        $this->assertEquals($data['name'], $savedPolicy->getName());
        $this->assertEquals($data['indexPattern'], $savedPolicy->getIndexPattern());
        $this->assertEquals($data['priority'] ?? null, $savedPolicy->getPriority());
        $this->assertEquals($data['description'] ?? '', $savedPolicy->getDescription());
        $this->assertEquals($data['deleteAfter'] ?? null, $savedPolicy->getDeleteAfter());
        $this->assertNotNull($savedPolicy->getSeqNo());
        $this->assertNotNull($savedPolicy->getPrimaryTerm());
    }

    public function createDataProvider(): iterable
    {
        yield 'missing name' => [
            ['description' => 'missing name'],
            \TypeError::class,
        ];

        yield 'missing indexPattern' => [
            ['name' => 'test', 'description' => 'missing indexPattern'],
            \TypeError::class,
        ];

        yield 'complete policy with all fields' => [
            ['name' => 'dummy-1', 'description' => 'Test ism', 'indexPattern' => 'gally_product_*', 'deleteAfter' => 365],
            null,
        ];

        yield 'policy without description' => [
            ['name' => 'dummy-2', 'indexPattern' => 'gally_category_*', 'deleteAfter' => 180],
            null,
        ];

        yield 'policy without deleteAfter' => [
            ['name' => 'no-delete-after', 'description' => 'Policy without deleteAfter', 'indexPattern' => 'gally_test_*'],
            null,
        ];
    }

    /**
     * @depends testCreate
     *
     * @dataProvider findByIdDataProvider
     */
    public function testFindById(string $policyId, ?array $expectedData): void
    {
        $foundPolicy = self::$repository->findById($policyId);

        if (null === $expectedData) {
            $this->assertNull($foundPolicy);

            return;
        }

        $this->assertNotNull($foundPolicy);
        $this->assertEquals($policyId, $foundPolicy->getId());
        $this->assertEquals($expectedData['name'], $foundPolicy->getName());
        $this->assertEquals($expectedData['indexPattern'], $foundPolicy->getIndexPattern());
        $this->assertEquals($expectedData['description'] ?? '', $foundPolicy->getDescription());
        $this->assertEquals($expectedData['deleteAfter'] ?? null, $foundPolicy->getDeleteAfter());
    }

    public function findByIdDataProvider(): iterable
    {
        yield [
            'gally_test__gally_dummy-1',
            ['name' => 'dummy-1', 'description' => 'Test ism', 'indexPattern' => 'gally_product_*', 'deleteAfter' => 365],
        ];

        yield [
            'gally_test__gally_dummy-2',
            ['name' => 'dummy-2', 'indexPattern' => 'gally_category_*', 'deleteAfter' => 180],
        ];

        yield [
            'gally_test__gally_no-delete-after',
            ['name' => 'no-delete-after', 'description' => 'Policy without deleteAfter', 'indexPattern' => 'gally_test_*'],
        ];

        yield [
            'gally_test__gally_non_existent',
            null,
        ];
    }

    /**
     * @depends testFindById
     */
    public function testUpdate(): void
    {
        $policy = self::$repository->findById('gally_test__gally_dummy-1');
        $policy->setDescription('Updated description');

        $policy = self::$repository->save($policy);

        $this->assertNotNull($policy);
        $this->assertEquals('gally_test__gally_dummy-1', $policy->getId());
        $this->assertEquals('dummy-1', $policy->getName());
        $this->assertEquals('gally_product_*', $policy->getIndexPattern());
        $this->assertEquals('Updated description', $policy->getDescription());
        $this->assertEquals(365, $policy->getDeleteAfter());
    }

    /**
     * @depends testUpdate
     */
    public function testDeletePolicy(): void
    {
        self::$repository->delete('gally_test__gally_dummy-1');

        $deletedPolicy = self::$repository->findById('gally_test__gally_dummy-1');
        $this->assertNull($deletedPolicy);
    }

    /**
     * @depends testDeletePolicy
     */
    public function testFindAll(): void
    {
        $policies = self::$repository->findAll();

        $this->assertGreaterThanOrEqual(2, \count($policies));

        $names = array_map(fn ($p) => $p->getName(), $policies);
        $this->assertContains('dummy-2', $names);
        $this->assertContains('no-delete-after', $names);
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
}
