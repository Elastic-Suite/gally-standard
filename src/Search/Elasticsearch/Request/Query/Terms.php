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
 * Gally request terms query.
 */
class Terms implements QueryInterface
{
    private ?string $name;

    private float $boost;

    private array $values;

    private string $field;

    /**
     * The terms query produce an Elasticsearch terms query.
     *
     * @param string|bool|array $values Search values. String are exploded using the comma as separator
     * @param string            $field  Search field
     * @param ?string           $name   Name of the query
     * @param float             $boost  Query boost
     */
    public function __construct(
        string|bool|array|int|float $values,
        string $field,
        ?string $name = null,
        float $boost = QueryInterface::DEFAULT_BOOST_VALUE
    ) {
        if (!\is_array($values) && \is_string($values)) {
            $values = explode(',', $values);
        } elseif (!\is_array($values)) {
            $values = [$values];
        }

        $this->name = $name;
        $this->values = $values;
        $this->field = $field;
        $this->boost = $boost;
    }

    public function getType(): string
    {
        return QueryInterface::TYPE_TERMS;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getBoost(): float
    {
        return $this->boost;
    }

    /**
     * Search field.
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * Get values.
     */
    public function getValues(): array
    {
        return $this->values;
    }
}
