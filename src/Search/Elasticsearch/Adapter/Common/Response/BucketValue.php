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

namespace Gally\Search\Elasticsearch\Adapter\Common\Response;

class BucketValue implements BucketValueInterface
{
    /**
     * @param AggregationInterface[] $childAggregation
     */
    public function __construct(
        private mixed $key,
        private int $count,
        private array $childAggregation,
    ) {
    }

    public function getKey(): mixed
    {
        return $this->key;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getChildAggregation(): iterable
    {
        return $this->childAggregation;
    }
}
