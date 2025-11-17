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
use Gally\Search\Elasticsearch\Request\SpanQueryInterface;

/**
 * Assemble an ES span term query.
 */
class SpanTerm implements AssemblerInterface
{
    public function assembleQuery(QueryInterface $query): array
    {
        if (SpanQueryInterface::TYPE_SPAN_TERM !== $query->getType()) {
            throw new \InvalidArgumentException("Query assembler : invalid query type {$query->getType()}");
        }

        /** @var \Gally\Search\Elasticsearch\Request\Query\SpanTerm $query */
        $searchQueryParams = [
            'value' => $query->getValue(),
            'boost' => $query->getBoost(),
        ];

        $searchQuery = ['span_term' => [$query->getField() => $searchQueryParams]];

        if ($query->getName()) {
            $searchQuery['span_term']['_name'] = $query->getName();
        }

        return $searchQuery;
    }
}
