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

use Gally\Search\Elasticsearch\Request\Query\SpanNear;
use Gally\Search\Elasticsearch\Request\Query\SpanTerm;
use Gally\Search\Elasticsearch\Request\QueryFactory;
use Gally\Search\Elasticsearch\Request\QueryInterface;
use Gally\Search\Elasticsearch\Request\SpanQueryInterface;
use PHPUnit\Framework\Constraint\IsType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SpanNearTest extends KernelTestCase
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
        self::$queryFactory->create(SpanQueryInterface::TYPE_SPAN_NEAR);
    }

    public function testDefaultCreate(): void
    {
        /** @var SpanNear $query */
        $query = self::$queryFactory->create(
            SpanQueryInterface::TYPE_SPAN_NEAR,
            [
                'clauses' => [new SpanTerm('red', 'color')],
            ]
        );
        $this->doStructureTest($query);

        $this->assertEquals([new SpanTerm('red', 'color')], $query->getClauses());
        $this->assertEquals(0, $query->getSlop());
        $this->assertTrue($query->isInOrder());
        $this->assertNull($query->getName());
        $this->assertEquals(QueryInterface::DEFAULT_BOOST_VALUE, $query->getBoost());
    }

    /**
     * @dataProvider spanTermDataProvider
     *
     * @param SpanQueryInterface[] $clauses Span near clauses
     * @param int                  $slop    Span near slop
     * @param bool                 $inOrder Span near in_order
     * @param ?string              $name    Name of the query
     * @param float                $boost   Query boost
     */
    public function testCreateComplexParams(
        array $clauses,
        int $slop,
        bool $inOrder,
        ?string $name = null,
        float $boost = 1
    ): void {
        // TODO: use reflection to build mapping ?
        $queryParams = [
            'clauses' => $clauses,
            'slop' => $slop,
            'inOrder' => $inOrder,
            'name' => $name,
            'boost' => $boost,
        ];

        /** @var SpanNear $query */
        $query = self::$queryFactory->create(SpanQueryInterface::TYPE_SPAN_NEAR, $queryParams);

        // Testing types.
        $this->doStructureTest($query);

        // Testing provided values.
        $this->assertEquals($clauses, $query->getClauses());
        $this->assertEquals($slop, $query->getSlop());
        $this->assertEquals($inOrder, $query->isInOrder());
        if ($name) {
            $this->assertEquals($name, $query->getName());
        }
        if ($boost) {
            $this->assertEquals($boost, $query->getBoost());
        }
    }

    public function spanTermDataProvider(): array
    {
        return [
            [
                [new SpanTerm('red', 'color')],
                10,
                false,
            ],
            [
                [new SpanTerm('red', 'color'), new SpanTerm('bleu', 'color'), new SpanTerm('green', 'color')],
                20,
                true,
            ],
            [
                [new SpanTerm('red', 'color'), new SpanTerm('bleu', 'color'), new SpanTerm('M', 'size')],
                20,
                true,
            ],
            [
                [new SpanTerm('red', 'color')],
                10,
                false,
                'span near query with name',
            ],
            [
                [new SpanTerm('red', 'color')],
                10,
                false,
                'span near query with name and boost',
                15,
            ],
        ];
    }

    private function doStructureTest(mixed $query): void
    {
        $this->assertInstanceOf(QueryInterface::class, $query);
        $this->assertInstanceOf(SpanQueryInterface::class, $query);
        $this->assertInstanceOf(SpanNear::class, $query);
        $this->assertEquals(SpanQueryInterface::TYPE_SPAN_NEAR, $query->getType());
        if ($query->getName()) {
            $this->assertIsString($query->getName());
        }
        $this->assertIsFloat($query->getBoost());

        /** @var SpanNear $query */
        $this->assertThat($query->getClauses(), new IsType('array'));
        $this->assertThat($query->getSlop(), new IsType('int'));
        $this->assertThat($query->isInOrder(), new IsType('bool'));
    }
}
