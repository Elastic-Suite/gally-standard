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

namespace Gally\Search\Tests\Unit\Elasticsearch\Request\Query;

use Gally\Search\Elasticsearch\Request\Query\DateRange;
use Gally\Search\Elasticsearch\Request\QueryFactory;
use Gally\Search\Elasticsearch\Request\QueryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DateRangeTest extends KernelTestCase
{
    private static QueryFactory $queryFactory;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        \assert(static::getContainer()->get(QueryFactory::class) instanceof QueryFactory);
        self::$queryFactory = static::getContainer()->get(QueryFactory::class);
    }

    public function testFailedCreate(): void
    {
        $this->expectException(\ArgumentCountError::class);
        self::$queryFactory->create(QueryInterface::TYPE_DATE_RANGE);
    }

    public function testDefaultCreate(): void
    {
        /** @var DateRange $query */
        $query = self::$queryFactory->create(QueryInterface::TYPE_DATE_RANGE, ['field' => 'created_at']);
        $this->doStructureTest($query);

        $this->assertEquals('created_at', $query->getField());
        $this->assertEmpty($query->getBounds());
        $this->assertNull($query->getName());
        $this->assertEquals(QueryInterface::DEFAULT_BOOST_VALUE, $query->getBoost());
    }

    /**
     * @dataProvider dateRangeDataProvider
     *
     * @param string      $field  Query field
     * @param array       $bounds Range filter bounds (authorized entries : gt, lt, lte, gte)
     * @param string|null $name   Query name
     * @param float|null  $boost  Query boost
     * @param string|null $format Date format
     */
    public function testCreateComplexParams(
        string $field,
        array $bounds,
        ?string $name,
        ?float $boost,
        ?string $format,
    ): void {
        // TODO: use reflection to build mapping ?
        $queryParams = [
            'field' => $field,
            'bounds' => $bounds,
            'name' => $name,
            'boost' => $boost,
            'format' => $format,
        ];
        $queryParams = array_filter(
            $queryParams,
            function ($param) {
                return \is_array($param) ? !empty($param) : (null !== $param && \strlen((string) $param));
            }
        );
        /** @var DateRange $query */
        $query = self::$queryFactory->create(QueryInterface::TYPE_DATE_RANGE, $queryParams);

        // Testing types.
        $this->doStructureTest($query);

        // Testing provided values.
        $this->assertEquals($field, $query->getField());
        $this->assertEquals($bounds, $query->getBounds());
        if ($name) {
            $this->assertEquals($name, $query->getName());
        }
        if ($boost) {
            $this->assertEquals($boost, $query->getBoost());
        }
        if ($format) {
            $this->assertEquals($format, $query->getFormat());
        }
    }

    public function dateRangeDataProvider(): array
    {
        return [
            [
                'create_at',
                ['gte' => '2023'],
                null,
                null,
                'yyyy',
            ],
            [
                'create_at',
                ['gte' => '2023'],
                'range query',
                null,
                'yyyy',
            ],
            [
                'create_at',
                ['gte' => '2023'],
                'range query with boost',
                15,
                'yyyy',
            ],
            [
                'create_at',
                ['gte' => '2023'],
                'range query with boost',
                15,
                'yyyy-MM-dd',
            ],
        ];
    }

    private function doStructureTest(mixed $query): void
    {
        $this->assertInstanceOf(QueryInterface::class, $query);
        $this->assertInstanceOf(DateRange::class, $query);
        $this->assertEquals(QueryInterface::TYPE_DATE_RANGE, $query->getType());
        if ($query->getName()) {
            $this->assertIsString($query->getName());
        }
        $this->assertIsFloat($query->getBoost());

        /** @var DateRange $query */
        $this->assertIsString($query->getField());
        $this->assertIsArray($query->getBounds());
    }
}
