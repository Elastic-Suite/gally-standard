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

namespace Gally\Search\Elasticsearch\Adapter\Common\Request\Query\Assembler;

use Gally\Search\Elasticsearch\Adapter\Common\Request\Query\AssemblerInterface;
use Gally\Search\Elasticsearch\Request\QueryInterface;

/**
 * Assemble an ES range query.
 */
class DateRange implements AssemblerInterface
{
    public function assembleQuery(QueryInterface $query): array
    {
        if (QueryInterface::TYPE_DATE_RANGE !== $query->getType()) {
            throw new \InvalidArgumentException("Query assembler : invalid query type {$query->getType()}");
        }
        /** @var \Gally\Search\Elasticsearch\Request\Query\DateRange $query */
        $queryParams = $query->getBounds();

        $queryParams['format'] = $query->getFormat();
        $queryParams['boost'] = $query->getBoost();

        $searchQuery = ['range' => [$query->getField() => $queryParams]];

        if ($query->getName()) {
            $searchQuery['range']['_name'] = $query->getName();
        }

        return $searchQuery;
    }
}
