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

namespace Gally\Search\Elasticsearch\Request\Aggregation\Bucket;

use Gally\Search\Elasticsearch\Request\AggregationInterface;
use Gally\Search\Elasticsearch\Request\BucketInterface;
use Gally\Search\Elasticsearch\Request\QueryInterface;

/**
 * Date range bucket implementation.
 */
class DateRange extends AbstractBucket
{
    /**
     * Constructor.
     *
     * @param string                 $name              Bucket name
     * @param string                 $field             Bucket field
     * @param array                  $ranges            List of date ranges
     * @param string                 $format            Date format
     * @param AggregationInterface[] $childAggregations Child aggregations
     * @param ?string                $nestedPath        Nested path for nested bucket
     * @param ?QueryInterface        $filter            Bucket filter
     * @param ?QueryInterface        $nestedFilter      Nested filter for the bucket
     */
    public function __construct(
        string $name,
        string $field,
        protected array $ranges,
        protected string $format = 'yyyy-MM-dd',
        array $childAggregations = [],
        ?string $nestedPath = null,
        ?QueryInterface $filter = null,
        ?QueryInterface $nestedFilter = null,
    ) {
        parent::__construct(
            $name,
            $field,
            $childAggregations,
            $nestedPath,
            $filter,
            $nestedFilter,
        );
    }

    public function getType(): string
    {
        return BucketInterface::TYPE_DATE_RANGE;
    }

    /**
     * Date ranges.
     */
    public function getRanges(): array
    {
        return $this->ranges;
    }

    /**
     * Date range format.
     */
    public function getFormat(): string
    {
        return $this->format;
    }
}
