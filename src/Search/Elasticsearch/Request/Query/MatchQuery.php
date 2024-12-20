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
 * Match query definition implementation.
 */
class MatchQuery implements QueryInterface
{
    /**
     * @var string
     */
    public const DEFAULT_MINIMUM_SHOULD_MATCH = '1';

    private ?string $name;

    private float $boost;

    private string $queryText;

    private string $field;

    private string $minimumShouldMatch;

    /**
     * Constructor.
     *
     * @param string  $queryText          Matched text
     * @param string  $field              Query field
     * @param string  $minimumShouldMatch Minimum should match for the match query
     * @param ?string $name               Query name
     * @param float   $boost              Query boost
     */
    public function __construct(
        string $queryText,
        string $field,
        string $minimumShouldMatch = self::DEFAULT_MINIMUM_SHOULD_MATCH,
        ?string $name = null,
        float $boost = QueryInterface::DEFAULT_BOOST_VALUE
    ) {
        $this->name = $name;
        $this->queryText = $queryText;
        $this->field = $field;
        $this->minimumShouldMatch = $minimumShouldMatch;
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
        return QueryInterface::TYPE_MATCH;
    }

    /**
     * Query match text.
     */
    public function getQueryText(): string
    {
        return $this->queryText;
    }

    /**
     * Query field.
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * Minimum should match for the match query.
     */
    public function getMinimumShouldMatch(): string
    {
        return $this->minimumShouldMatch;
    }
}
