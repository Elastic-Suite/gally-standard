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

namespace Gally\Search\Elasticsearch\Adapter\Common\Request\SortOrder;

use Gally\Index\Entity\Index\Mapping\FieldInterface;
use Gally\Search\Elasticsearch\Adapter\Common\Request\Query\Assembler as QueryAssembler;
use Gally\Search\Elasticsearch\Builder\Request\SortOrder\GeoDistance;
use Gally\Search\Elasticsearch\Builder\Request\SortOrder\Nested;
use Gally\Search\Elasticsearch\Builder\Request\SortOrder\Script;
use Gally\Search\Elasticsearch\Builder\Request\SortOrder\Standard;
use Gally\Search\Elasticsearch\Request\SortOrderInterface;

class Assembler
{
    /**
     * Constructor.
     *
     * @param QueryAssembler $queryAssembler Query assembler used to assemble queries inside sort orders
     */
    public function __construct(private QueryAssembler $queryAssembler)
    {
    }

    /**
     * Build sort orders.
     *
     * @param SortOrderInterface[] $sortOrders sort orders specification
     */
    public function assembleSortOrders(array $sortOrders = []): array
    {
        return array_map([$this, 'assembleSortOrder'], $sortOrders);
    }

    /**
     * Assemble a sort order ES condition from a SortOrderInterface specification.
     *
     * @param SortOrderInterface $sortOrder request sort order specification object
     */
    private function assembleSortOrder(SortOrderInterface $sortOrder): array
    {
        $sortField = $sortOrder->getField();

        $sortOrderConfig = ['order' => $sortOrder->getDirection()];

        if (SortOrderInterface::TYPE_SCRIPT === $sortOrder->getType()) {
            /** @var Script $sortOrder */
            $sortOrderConfig['type'] = $sortOrder->getScriptType();
            $sortOrderConfig['script'] = $sortOrder->getScript();
        } elseif (SortOrderInterface::TYPE_DISTANCE === $sortOrder->getType()) {
            /** @var GeoDistance $sortOrder */
            $sortOrderConfig[$sortField] = $sortOrder->getReferenceLocation();
            $sortOrderConfig['unit'] = $sortOrder->getUnit();
            $sortOrderConfig['mode'] = $sortOrder->getMode();
            $sortOrderConfig['distance_type'] = $sortOrder->getDistanceType();
            $sortOrderConfig['ignore_unmapped'] = $sortOrder->getIgnoreUnmapped();
            $sortField = GeoDistance::GEO_DISTANCE_FIELD;
        } elseif (SortOrderInterface::DEFAULT_SORT_FIELD !== $sortField
            && \in_array($sortOrder->getType(), [SortOrderInterface::TYPE_STANDARD, SortOrderInterface::TYPE_NESTED, SortOrderInterface::TYPE_SCRIPT], true)) {
            /** @var Standard|Nested|Script $sortOrder */
            $sortOrderConfig['missing'] = $sortOrder->getMissing();
            $sortOrderConfig['unmapped_type'] = FieldInterface::FIELD_TYPE_KEYWORD;
        }

        if (SortOrderInterface::TYPE_NESTED == $sortOrder->getType()) {
            /** @var Nested $sortOrder */
            $sortOrderConfig['nested']['path'] = $sortOrder->getNestedPath();
            $sortOrderConfig['mode'] = $sortOrder->getScoreMode();

            if ($sortOrder->getNestedFilter()) {
                $sortOrderConfig['nested']['filter'] = $this->queryAssembler->assembleQuery($sortOrder->getNestedFilter());
            }
        }

        return [$sortField => $sortOrderConfig];
    }
}
