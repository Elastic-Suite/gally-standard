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

namespace Gally\Search\Elasticsearch\Request\Query;

use Gally\Search\Elasticsearch\Request\SpanQueryInterface;

/**
 * Gally request span near query.
 */
class SpanNear implements SpanQueryInterface
{
    private ?string $name;
    private float $boost;

    /** @var SpanQueryInterface[] */
    private array $clauses;
    private int $slop;
    private bool $inOrder;

    /**
     * The span near query produce an Elasticsearch span near query.
     *
     * @param SpanQueryInterface[] $clauses a list of one or more other span type queries
     * @param int                  $slop    slop controls the maximum number of intervening unmatched positions permitted
     * @param bool                 $inOrder indicate if the query should match queries in the same order
     * @param ?string              $name    Name of the query
     * @param float                $boost   Query boost
     */
    public function __construct(
        array $clauses,
        int $slop = 0,
        bool $inOrder = true,
        ?string $name = null,
        float $boost = SpanQueryInterface::DEFAULT_BOOST_VALUE
    ) {
        $this->name = $name;
        $this->clauses = $clauses;
        $this->slop = $slop;
        $this->inOrder = $inOrder;
        $this->boost = $boost;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getBoost(): float
    {
        return $this->boost;
    }

    public function getType(): string
    {
        return SpanQueryInterface::TYPE_SPAN_NEAR;
    }

    /**
     * Get clauses.
     *
     * @return SpanQueryInterface[]
     */
    public function getClauses(): array
    {
        return $this->clauses;
    }

    /**
     * Get slop value.
     */
    public function getSlop(): int
    {
        return $this->slop;
    }

    /**
     * Get in_order value.
     */
    public function isInOrder(): bool
    {
        return $this->inOrder;
    }
}
