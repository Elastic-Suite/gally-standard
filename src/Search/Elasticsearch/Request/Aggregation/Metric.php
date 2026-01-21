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

namespace Gally\Search\Elasticsearch\Request\Aggregation;

use Gally\Search\Elasticsearch\Request\MetricInterface;
use Gally\Search\Elasticsearch\Request\QueryInterface;

/**
 * Metrics aggregation.
 */
class Metric implements MetricInterface
{
    /**
     * @param string $name   Bucket name
     * @param string $field  Bucket field
     * @param string $type   Metric type
     * @param array  $config Metric extra config
     */
    public function __construct(
        private string $name,
        private string $field,
        private string $type = MetricInterface::TYPE_STATS,
        private array $config = [],
        private ?string $nestedPath = null,
        private ?QueryInterface $nestedFilter = null,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getConfig(): array
    {
        return $this->config;
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
}
