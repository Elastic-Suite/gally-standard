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
 * ES common query definition implementation.
 */
class Common extends MatchQuery
{
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
        parent::__construct($queryText, $field, $minimumShouldMatch, $name, $boost);
    }

    public function getType(): string
    {
        return QueryInterface::TYPE_COMMON;
    }
}
