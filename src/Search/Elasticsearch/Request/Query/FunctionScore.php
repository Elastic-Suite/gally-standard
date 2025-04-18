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
 * Query negation definition implementation.
 */
class FunctionScore implements QueryInterface
{
    private ?string $name;

    private QueryInterface $query;

    private string $scoreMode;

    private string $boostMode;

    private ?float $minScore;

    /**
     * @var array<mixed>
     */
    private array $functions;

    /**
     * Score mode functions.
     */
    public const SCORE_MODE_MULTIPLY = 'multiply';
    public const SCORE_MODE_SUM = 'sum';
    public const SCORE_MODE_AVG = 'avg';
    public const SCORE_MODE_FIRST = 'first';
    public const SCORE_MODE_MAX = 'max';
    public const SCORE_MODE_MIN = 'min';

    /**
     * Boost mode functions.
     */
    public const BOOST_MODE_MULTIPLY = 'multiply';
    public const BOOST_MODE_SUM = 'sum';
    public const BOOST_MODE_AVG = 'avg';
    public const BOOST_MODE_FIRST = 'first';
    public const BOOST_MODE_MAX = 'max';
    public const BOOST_MODE_MIN = 'min';

    /**
     * Functions score list.
     */
    public const FUNCTION_SCORE_SCRIPT_SCORE = 'script_score';
    public const FUNCTION_SCORE_WEIGHT = 'weight';
    public const FUNCTION_SCORE_RANDOM_SCORE = 'random_score';
    public const FUNCTION_SCORE_FIELD_VALUE_FACTOR = 'field_value_factor';

    /**
     * Constructor.
     *
     * @param QueryInterface $query     Original query to be wrapped by the functions scoring
     * @param array<mixed>   $functions Function score
     * @param ?string        $name      Query name
     * @param string         $scoreMode Score mode
     * @param string         $boostMode Boost mode
     */
    public function __construct(
        QueryInterface $query,
        array $functions = [],
        ?string $name = null,
        string $scoreMode = self::SCORE_MODE_SUM,
        string $boostMode = self::BOOST_MODE_SUM,
        ?float $minScore = null,
    ) {
        $this->name = $name;
        $this->query = $query;
        $this->scoreMode = $scoreMode;
        $this->boostMode = $boostMode;
        $this->minScore = $minScore;
        $this->functions = $functions;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getBoost(): ?float
    {
        return null;
    }

    public function getType(): string
    {
        return QueryInterface::TYPE_FUNCTIONSCORE;
    }

    /**
     * Returns score mode.
     */
    public function getScoreMode(): string
    {
        return $this->scoreMode;
    }

    /**
     * Returns boost mode.
     */
    public function getBoostMode(): string
    {
        return $this->boostMode;
    }

    /**
     * Returns min score.
     */
    public function getMinScore(): ?float
    {
        return $this->minScore;
    }

    /**
     * Return function score base query.
     */
    public function getQuery(): QueryInterface
    {
        return $this->query;
    }

    /**
     * Returns function score.
     *
     * @return array<mixed>
     */
    public function getFunctions(): array
    {
        return $this->functions;
    }
}
