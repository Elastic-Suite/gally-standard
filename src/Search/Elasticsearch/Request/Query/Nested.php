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

use Gally\Search\Elasticsearch\Request\QueryInterface;

/**
 * Nested queries definition implementation.
 */
class Nested implements QueryInterface
{
    public const SCORE_MODE_AVG = 'avg';
    public const SCORE_MODE_SUM = 'sum';
    public const SCORE_MODE_MIN = 'min';
    public const SCORE_MODE_MAX = 'max';
    public const SCORE_MODE_NONE = 'none';

    private string $scoreMode;

    private ?string $name;

    private float $boost;

    private string $path;

    private ?QueryInterface $query;

    /**
     * Constructor.
     *
     * @param string          $path      nested path
     * @param ?QueryInterface $query     nested query
     * @param string          $scoreMode Score mode of the nested query
     * @param ?string         $name      query name
     * @param float           $boost     query boost
     */
    public function __construct(
        string $path,
        ?QueryInterface $query = null,
        string $scoreMode = self::SCORE_MODE_NONE,
        ?string $name = null,
        float $boost = QueryInterface::DEFAULT_BOOST_VALUE
    ) {
        $this->name = $name;
        $this->boost = $boost;
        $this->path = $path;
        $this->scoreMode = $scoreMode;
        $this->query = $query;
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
        return QueryInterface::TYPE_NESTED;
    }

    /**
     * Nested query path.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Nested query score mode.
     */
    public function getScoreMode(): string
    {
        return $this->scoreMode;
    }

    /**
     * Nested query.
     */
    public function getQuery(): ?QueryInterface
    {
        return $this->query;
    }
}
