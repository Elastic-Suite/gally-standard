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
 * Gally date range query implementation.
 */
class DateRange extends Range
{
    private string $format;

    /**
     * Constructor.
     *
     * @param string  $field  Query field
     * @param array   $bounds Range filter bounds (authorized entries : gt, lt, lte, gte)
     * @param ?string $name   Query name
     * @param float   $boost  Query boost
     */
    public function __construct(
        string $field,
        array $bounds = [],
        ?string $name = null,
        float $boost = QueryInterface::DEFAULT_BOOST_VALUE,
        string $format = 'yyyy-MM-dd'
    ) {
        parent::__construct($field, $bounds, $name, $boost);
        $this->format = $format;
    }

    public function getType(): string
    {
        return QueryInterface::TYPE_DATE_RANGE;
    }

    /**
     * Range filter format.
     */
    public function getFormat(): string
    {
        return $this->format;
    }
}
