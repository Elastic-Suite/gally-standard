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

namespace Gally\Search\State;

use ApiPlatform\State\Pagination\PaginatorInterface;
use Gally\Search\Elasticsearch\Adapter\Common\Response\AggregationInterface;
use Gally\Search\Elasticsearch\Request\ContainerConfigurationInterface;
use Gally\Search\Elasticsearch\Request\SortOrderInterface;
use Gally\Search\Elasticsearch\RequestInterface;
use Gally\Search\Elasticsearch\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class Paginator implements \IteratorAggregate, PaginatorInterface
{
    use PaginatorTrait;
    protected array $cachedDenormalizedDocuments = [];

    public function __construct(
        protected DenormalizerInterface $denormalizer,
        protected ContainerConfigurationInterface $containerConfiguration,
        protected RequestInterface $request,
        protected ResponseInterface $response,
        protected string $resourceClass,
        protected int $limit,
        protected int $offset,
        protected array $denormalizationContext = [],
    ) {
    }

    public function count(): int
    {
        return $this->response->count();
    }

    public function getLastPage(): float
    {
        if (0 >= $this->limit) {
            return 1.;
        }

        return ceil($this->getTotalItems() / $this->limit) ?: 1.;
    }

    public function getTotalItems(): float
    {
        return (float) $this->response->getTotalItems();
    }

    public function getCurrentPage(): float
    {
        if (0 >= $this->limit) {
            return 1.;
        }

        return floor($this->offset / $this->limit) + 1.;
    }

    public function getItemsPerPage(): float
    {
        return (float) $this->limit;
    }

    public function getIterator(): \Traversable
    {
        return $this->getPaginatorIterator($this->response->getIterator());
    }

    /**
     * Get aggregations.
     *
     * @return AggregationInterface[]
     */
    public function getAggregations(): array
    {
        return $this->response->getAggregations();
    }

    /**
     * Get applied sort orders.
     *
     * @return SortOrderInterface[]
     */
    public function getCurrentSortOrders(): array
    {
        return $this->request->getSortOrders();
    }

    /**
     * Get container configuration that generate the request.
     */
    public function getContainerConfig(): ContainerConfigurationInterface
    {
        return $this->containerConfiguration;
    }
}
