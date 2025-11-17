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
 * Geo distance bucket implementation.
 */
class GeoDistance extends AbstractBucket
{
    private string $origin;
    private string $unit;
    private array $ranges;

    /**
     * Constructor.
     *
     * @param string                 $name              Bucket name
     * @param string                 $field             Bucket field
     * @param string                 $origin            Bucket reference location
     * @param string                 $unit              Bucket unit
     * @param array                  $ranges            Bucket ranges
     * @param AggregationInterface[] $childAggregations Child aggregations
     * @param ?string                $nestedPath        Nested path for nested bucket
     * @param ?QueryInterface        $filter            Bucket filter
     * @param ?QueryInterface        $nestedFilter      Nested filter for the bucket
     */
    public function __construct(
        string $name,
        string $field,
        string $origin,
        array $ranges,
        string $unit = 'km',
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
        $this->origin = $origin;
        $this->unit = $unit;

        if (empty($ranges)) {
            throw new \InvalidArgumentException('Geo distance aggregation ranges cannot be empty.');
        }
        foreach ($ranges as $range) {
            if (!\is_array($range) || (!\array_key_exists('from', $range) && !\array_key_exists('to', $range))) {
                throw new \InvalidArgumentException('Invalid geo distance aggregation range.');
            }
        }

        $this->ranges = $ranges;
    }

    public function getType(): string
    {
        return BucketInterface::TYPE_GEO_DISTANCE;
    }

    /**
     * Date geo distance reference location.
     */
    public function getOrigin(): string
    {
        return $this->origin;
    }

    /**
     * Date geo distance bucket unit.
     */
    public function getUnit(): string
    {
        return $this->unit;
    }

    /**
     * Date geo distance ranges.
     */
    public function getRanges(): array
    {
        return $this->ranges;
    }
}
