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

namespace Gally\Tracker\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Metadata\Repository\MetadataRepository;
use Gally\Search\Elasticsearch\Adapter;
use Gally\Search\Elasticsearch\Builder\Request\SimpleRequestBuilder as RequestBuilder;
use Gally\Search\Elasticsearch\Request\Container\Configuration\ContainerConfigurationProvider;
use Gally\Search\Elasticsearch\ResponseInterface;
use Gally\Tracker\Entity\Kpi;

class KpiProvider implements ProviderInterface
{
    public function __construct(
        private RequestBuilder $requestBuilder,
        private ContainerConfigurationProvider $containerConfigurationProvider,
        private Adapter $adapter,
        private MetadataRepository $metadataRepository,
        private LocalizedCatalogRepository $localizedCatalogRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $localizedCatalogCode = $context['filters']['localizedCatalog'] ?? null;
        $startDate = $context['filters']['startDate'] ?? null;
        $endDate = $context['filters']['endDate'] ?? null;

        if (!$localizedCatalogCode) {
            throw new \InvalidArgumentException('localizedCatalog filter is required');
        }

        $localizedCatalog = $this->localizedCatalogRepository->findByCodeOrId($localizedCatalogCode);
        $metadata = $this->metadataRepository->findByEntity('tracking_event');
        $containerConfig = $this->containerConfigurationProvider->get(
            $metadata,
            $localizedCatalog,
            'tracking_kpi'
        );

        $filters = $this->buildDateRangeFilter($startDate, $endDate);

        $request = $this->requestBuilder->create(
            $containerConfig,
            0,
            0,
            null,
            [],
            $filters,
            [],
            []
        );

        $response = $this->adapter->search($request);

        $kpi = $this->buildKpiFromResponse($response);
        $kpi->setLocalizedCatalog($localizedCatalog->getCode());
        $kpi->setStartDate($startDate);
        $kpi->setEndDate($endDate);

        return [$kpi];
    }

    private function buildDateRangeFilter(?string $startDate, ?string $endDate): array
    {
        if (!$startDate && !$endDate) {
            return [];
        }

        $rangeFilter = [];

        if ($startDate) {
            $rangeFilter['gte'] = $startDate;
        }

        if ($endDate) {
            $rangeFilter['lte'] = $endDate;
        }

        return ['@timestamp' => $rangeFilter];
    }

    private function buildKpiFromResponse(ResponseInterface $response): Kpi
    {
        $aggregations = $response->getAggregations();
        $kpi = new Kpi();

        if (isset($aggregations['count_by_event'])) {
            $eventBuckets = $aggregations['count_by_event']->getValues();

            /** @var Adapter\Common\Response\BucketValueInterface $eventBucket */
            foreach ($eventBuckets as $eventBucket) {
                $eventType = $eventBucket->getKey();
                $metadataBuckets = $eventBucket->getChildAggregation()['count_by_metadata']?->getValues() ?? [];

                foreach ($metadataBuckets as $metadataBucket) {
                    $metadataCode = $metadataBucket->getKey();
                    $count = $metadataBucket->getCount();

                    // Map event_type x metadata_code to KPI properties
                    if ('view' === $eventType && 'product' === $metadataCode) {
                        $kpi->setProductViewCount($count);
                    } elseif ('view' === $eventType && 'category' === $metadataCode) {
                        $kpi->setCategoryViewCount($count);
                    } elseif ('search' === $eventType && 'product' === $metadataCode) {
                        $kpi->setSearchCount($count);
                    } elseif ('add_to_cart' === $eventType && 'product' === $metadataCode) {
                        $kpi->setAddToCartCount($count);
                    }
                }
            }
        }

        if (\array_key_exists('session_count', $aggregations)) {
            $kpi->setSessionCount($aggregations['session_count']->getValues()['value'] ?? 0);
        }
        if (\array_key_exists('visitor_count', $aggregations)) {
            $kpi->setVisitorCount($aggregations['visitor_count']->getValues()['value'] ?? 0);
        }
        if (\array_key_exists('order_count', $aggregations)) {
            $kpi->setOrderCount($aggregations['order_count']->getValues()['value'] ?? 0);
        }

        return $kpi;
    }
}
