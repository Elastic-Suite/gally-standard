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
 * Bucket aggregation interface with support for nested and filtered aggregations.
 */
interface BucketInterface extends AggregationInterface
{
    public const TYPE_TERMS = 'termsBucket';
    public const TYPE_RANGE = 'rangeBucket';
    public const TYPE_DYNAMIC = 'dynamicBucket';
    public const TYPE_MULTI_TERMS = 'multiTermsBucket';

    public const TYPE_HISTOGRAM = 'histogramBucket';
    public const TYPE_DATE_HISTOGRAM = 'dateHistogramBucket';
    public const TYPE_DATE_RANGE = 'dateRangeBucket';
    public const TYPE_QUERY_GROUP = 'queryGroupBucket';
    public const TYPE_SIGNIFICANT_TERMS = 'significantTermsBucket';
    public const TYPE_REVERSE_NESTED = 'reverseNestedBucket';
    public const TYPE_GEO_DISTANCE = 'geoDistanceBucket';

    public const SORT_ORDER_COUNT = '_count';
    public const SORT_ORDER_TERM = '_term';
    public const SORT_ORDER_RELEVANCE = '_score';
    public const SORT_ORDER_MANUAL = '_manual';

    public const FIELD_VALUE = 'value';

    /**
     * @var int
     */
    public const MAX_BUCKET_SIZE = 100000;

    /**
     * Bucket field.
     */
    public function getField(): string;

    /**
     * Indicates if the aggregation is nested.
     */
    public function isNested(): bool;

    /**
     * Nested path for nested aggregations.
     */
    public function getNestedPath(): ?string;

    /**
     * Optional filter for nested filters (eg. filter by customer group for price).
     */
    public function getNestedFilter(): ?QueryInterface;

    /**
     * Optional filter for filtered aggregations.
     */
    public function getFilter(): ?QueryInterface;

    /**
     * Returns child aggregations.
     *
     * @return AggregationInterface[]
     */
    public function getChildAggregations(): array;
}
