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

namespace Gally\Search\Elasticsearch\Request;

/**
 * Usable query types.
 */
interface QueryInterface
{
    public const DEFAULT_BOOST_VALUE = 1;

    /**
     * Query types.
     */
    public const TYPE_MATCH = 'matchQuery';
    public const TYPE_BOOL = 'boolQuery';
    public const TYPE_FILTER = 'filteredQuery';
    public const TYPE_NESTED = 'nestedQuery';
    public const TYPE_RANGE = 'rangeQuery';
    public const TYPE_DATE_RANGE = 'dateRangeQuery';
    public const TYPE_GEO_DISTANCE = 'geoDistanceQuery';
    public const TYPE_TERM = 'termQuery';
    public const TYPE_TERMS = 'termsQuery';
    public const TYPE_NOT = 'notQuery';
    public const TYPE_MULTIMATCH = 'multiMatchQuery';
    public const TYPE_COMMON = 'commonQuery';
    public const TYPE_EXISTS = 'existsQuery';
    public const TYPE_MISSING = 'missingQuery';
    public const TYPE_FUNCTIONSCORE = 'functionScore';
    public const TYPE_MORELIKETHIS = 'moreLikeThisQuery';
    public const TYPE_MATCHPHRASEPREFIX = 'matchPhrasePrefixQuery';

    /**
     * Get query type.
     */
    public function getType(): string;

    /**
     * Get name.
     */
    public function getName(): ?string;

    /**
     * Get boost.
     */
    public function getBoost(): ?float;
}
