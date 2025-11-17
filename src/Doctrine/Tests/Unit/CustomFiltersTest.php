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

namespace Gally\Doctrine\Tests\Unit;

use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\IdentifiersExtractorInterface;
use ApiPlatform\Metadata\IriConverterInterface;
use Doctrine\Persistence\ManagerRegistry;
use Gally\Doctrine\Filter\JsonFilter;
use Gally\Doctrine\Filter\RangeFilterWithDefault;
use Gally\Doctrine\Filter\SearchColumnsFilter;
use Gally\Doctrine\Filter\SearchFilter;
use Gally\Doctrine\Filter\SearchFilterWithDefault;
use Gally\Doctrine\Tests\Entity\FakeEntity;
use Gally\Test\AbstractTestCase;
use Gally\User\Constant\Role;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class CustomFiltersTest extends AbstractTestCase
{
    /**
     * @dataProvider applyCustomSearchFilterDataProvider
     */
    public function testCustomSearchFilter(
        string $filterClass,
        array $properties,
        array $filterValue,
        string $expectedDQL,
        array $additionalArguments = [],
    ): void {
        $manager = static::getContainer()->get(ManagerRegistry::class);
        $resourceClass = FakeEntity::class;

        /** @var FilterInterface $filter */
        $filter = new $filterClass(
            $manager,
            static::getContainer()->get(IriConverterInterface::class),
            static::getContainer()->get(PropertyAccessorInterface::class),
            null,
            $properties,
            static::getContainer()->get(IdentifiersExtractorInterface::class),
            ...$additionalArguments
        );

        $queryBuilder = $manager->getRepository($resourceClass)->createQueryBuilder('o');
        $filter->apply($queryBuilder, new QueryNameGenerator(), $resourceClass, null, ['filters' => $filterValue]);

        $this->assertEquals($expectedDQL, $queryBuilder->getQuery()->getDQL());
    }

    public function applyCustomSearchFilterDataProvider(): iterable
    {
        yield [
            SearchFilter::class, // Filter class
            ['name' => 'exact'], // Properties
            ['name' => ['fr_FR']], // Filter value
            "SELECT o FROM Gally\Doctrine\Tests\Entity\FakeEntity o WHERE o.name = :name_p1", // Expected DQL
        ];
        yield [
            SearchFilter::class, // Filter class
            ['name' => 'partial'], // Properties
            ['name' => ['fr_FR']], // Filter value
            "SELECT o FROM Gally\Doctrine\Tests\Entity\FakeEntity o WHERE o.name LIKE CONCAT('%', :name_p1_0, '%')", // Expected DQL
        ];
        yield [
            SearchFilter::class, // Filter class
            ['name' => 'ipartial'], // Properties
            ['name' => ['fr_FR']], // Filter value
            "SELECT o FROM Gally\Doctrine\Tests\Entity\FakeEntity o WHERE LOWER(o.name) LIKE LOWER(CONCAT('%', :name_p1_0, '%'))", // Expected DQL
        ];
        yield [
            SearchFilterWithDefault::class, // Filter class
            ['code' => 'ipartial'], // Properties
            ['code' => ['42']], // Filter value
            "SELECT o FROM Gally\Doctrine\Tests\Entity\FakeEntity o WHERE LOWER((CASE WHEN o.code IS NULL THEN (CASE WHEN default.code IS NOT NULL THEN default.code ELSE '0' END) ELSE o.code END)) LIKE LOWER(CONCAT('%', :code_p1_0, '%'))", // Expected DQL
            ['defaultValues' => ['code' => '0']], // Additional constructor arguments
        ];
        yield [
            SearchColumnsFilter::class, // Filter class
            ['code' => ['name']], // Properties
            ['code' => ['test']], // Filter value
            "SELECT o FROM Gally\Doctrine\Tests\Entity\FakeEntity o WHERE LOWER(o.name) LIKE LOWER(CONCAT('%', :name_p1, '%')) OR LOWER(o.code) LIKE LOWER(CONCAT('%', :code_p2, '%'))", // Expected DQL
        ];
    }

    /**
     * @dataProvider applyCustomRangeFilterDataProvider
     */
    public function testCustomRangeFilter(
        string $filterClass,
        array $properties,
        array $filterValue,
        string $expectedDQL,
        array $additionalArguments = [],
    ): void {
        $manager = static::getContainer()->get(ManagerRegistry::class);
        $resourceClass = FakeEntity::class;

        /** @var FilterInterface $filter */
        $filter = new $filterClass(
            $manager,
            static::getContainer()->get(LoggerInterface::class),
            $properties,
            ...$additionalArguments
        );

        $queryBuilder = $manager->getRepository($resourceClass)->createQueryBuilder('o');
        $filter->apply($queryBuilder, new QueryNameGenerator(), $resourceClass, null, ['filters' => $filterValue]);

        $this->assertEquals($expectedDQL, $queryBuilder->getQuery()->getDQL());
    }

    public function applyCustomRangeFilterDataProvider(): iterable
    {
        yield [
            RangeFilter::class, // Filter class
            ['weight' => 'exact'], // Properties
            ['weight' => ['between' => '42..100']], // Filter value
            "SELECT o FROM Gally\Doctrine\Tests\Entity\FakeEntity o WHERE o.weight BETWEEN :weight_p1_1 AND :weight_p1_2", // Expected DQL
        ];
        yield [
            RangeFilterWithDefault::class, // Filter class
            ['weight' => 'exact'], // Properties
            ['weight' => ['between' => '42..100']], // Filter value
            "SELECT o FROM Gally\Doctrine\Tests\Entity\FakeEntity o WHERE (CASE WHEN o.weight IS NULL THEN (CASE WHEN default.weight IS NOT NULL THEN default.weight ELSE '100' END) ELSE o.weight END) >= :weight_p1_1 AND (CASE WHEN o.weight IS NULL THEN (CASE WHEN default.weight IS NOT NULL THEN default.weight ELSE '100' END) ELSE o.weight END) <= :weight_p1_2", // Expected DQL,
            ['defaultValues' => ['weight' => '100']], // Additional constructor arguments
        ];
        yield [
            RangeFilterWithDefault::class, // Filter class
            ['weight' => 'exact'], // Properties
            ['weight' => ['lt' => '100']], // Filter value
            "SELECT o FROM Gally\Doctrine\Tests\Entity\FakeEntity o WHERE (CASE WHEN o.weight IS NULL THEN (CASE WHEN default.weight IS NOT NULL THEN default.weight ELSE '100' END) ELSE o.weight END) < :weight_p1", // Expected DQL,
            ['defaultValues' => ['weight' => '100']], // Additional constructor arguments
        ];
        yield [
            RangeFilterWithDefault::class, // Filter class
            ['weight' => 'exact'], // Properties
            ['weight' => ['gte' => '42']], // Filter value
            "SELECT o FROM Gally\Doctrine\Tests\Entity\FakeEntity o WHERE (CASE WHEN o.weight IS NULL THEN (CASE WHEN default.weight IS NOT NULL THEN default.weight ELSE '100' END) ELSE o.weight END) >= :weight_p1", // Expected DQL,
            ['defaultValues' => ['weight' => '100']], // Additional constructor arguments
        ];
    }

    /**
     * @dataProvider applyCustomFilterDataProvider
     */
    public function testCustomFilter(
        string $filterClass,
        array $properties,
        array $filterValue,
        string $expectedDQL,
        array $additionalArguments = [],
    ): void {
        $manager = static::getContainer()->get(ManagerRegistry::class);
        $resourceClass = FakeEntity::class;

        /** @var FilterInterface $filter */
        $filter = new $filterClass(
            $manager,
            static::getContainer()->get(LoggerInterface::class),
            $properties,
        );

        $queryBuilder = $manager->getRepository($resourceClass)->createQueryBuilder('o');
        $filter->apply($queryBuilder, new QueryNameGenerator(), $resourceClass, null, ['filters' => $filterValue]);

        $this->assertEquals($expectedDQL, $queryBuilder->getQuery()->getDQL());
    }

    public function applyCustomFilterDataProvider(): iterable
    {
        yield [
            JsonFilter::class, // Filter class
            ['roles' => null], // Properties
            ['roles' => Role::ROLE_ADMIN], // Filter value
            "SELECT o FROM Gally\Doctrine\Tests\Entity\FakeEntity o WHERE JSONB_EXISTS_ANY(o.roles, ARRAY(:roles0_p1)) = true", // Expected DQL
        ];
        yield [
            JsonFilter::class, // Filter class
            ['roles' => null], // Properties
            ['roles' => [Role::ROLE_ADMIN]], // Filter value
            "SELECT o FROM Gally\Doctrine\Tests\Entity\FakeEntity o WHERE JSONB_EXISTS_ANY(o.roles, ARRAY(:roles0_p1)) = true", // Expected DQL
        ];
        yield [
            JsonFilter::class, // Filter class
            ['roles' => null], // Properties
            ['roles' => [Role::ROLE_ADMIN, Role::ROLE_CONTRIBUTOR]], // Filter value
            "SELECT o FROM Gally\Doctrine\Tests\Entity\FakeEntity o WHERE JSONB_EXISTS_ANY(o.roles, ARRAY(:roles0_p1, :roles1_p2)) = true", // Expected DQL
        ];
    }
}
