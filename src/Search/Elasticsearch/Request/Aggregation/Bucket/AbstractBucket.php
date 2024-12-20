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
 * Abstract bucket implementation.
 */
abstract class AbstractBucket implements BucketInterface
{
    private string $name;

    private string $field;

    /**
     * @var AggregationInterface[]
     */
    private array $childAggregations;

    private ?string $nestedPath;

    private ?QueryInterface $filter;

    private ?QueryInterface $nestedFilter;

    /**
     * Constructor.
     *
     * @param string                 $name              Bucket name
     * @param string                 $field             Bucket field
     * @param AggregationInterface[] $childAggregations Child aggregations
     * @param ?string                $nestedPath        Nested path for nested bucket
     * @param ?QueryInterface        $filter            Bucket filter
     * @param ?QueryInterface        $nestedFilter      Nested filter for the bucket
     */
    public function __construct(
        string $name,
        string $field,
        array $childAggregations = [],
        ?string $nestedPath = null,
        ?QueryInterface $filter = null,
        ?QueryInterface $nestedFilter = null
    ) {
        $this->name = $name;
        $this->field = $field;
        $this->childAggregations = $childAggregations;
        $this->nestedPath = $nestedPath;
        $this->filter = $filter;
        $this->nestedFilter = $nestedFilter;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isNested(): bool
    {
        return null !== $this->nestedPath;
    }

    public function getNestedPath(): ?string
    {
        return $this->nestedPath;
    }

    public function getNestedFilter(): ?QueryInterface
    {
        return $this->nestedFilter;
    }

    public function getFilter(): ?QueryInterface
    {
        return $this->filter;
    }

    public function getChildAggregations(): array
    {
        return $this->childAggregations;
    }
}
