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

use Gally\Search\Elasticsearch\Request\SpanQueryInterface;

/**
 * Gally request span term query.
 */
class SpanTerm implements SpanQueryInterface
{
    private ?string $name;
    private float $boost;
    private string $value;
    private string $field;

    /**
     * The span term query produce an Elasticsearch span term query.
     *
     * @param string  $value Search value
     * @param string  $field Search field
     * @param ?string $name  Name of the query
     * @param float   $boost Query boost
     */
    public function __construct(
        string $value,
        string $field,
        ?string $name = null,
        float $boost = SpanQueryInterface::DEFAULT_BOOST_VALUE
    ) {
        $this->name = $name;
        $this->value = $value;
        $this->field = $field;
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
        return SpanQueryInterface::TYPE_SPAN_TERM;
    }

    /**
     * Search value.
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Search field.
     */
    public function getField(): string
    {
        return $this->field;
    }
}
