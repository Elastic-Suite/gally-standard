<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @package   Gally
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Gally\Search\DataProvider;

use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\Pagination;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Category\Service\CurrentCategoryProvider;
use Gally\Entity\Service\PriceGroupProvider;
use Gally\Entity\Service\ReferenceLocationProvider;
use Gally\Metadata\Repository\MetadataRepository;
use Gally\Search\Elasticsearch\Adapter;
use Gally\Search\Elasticsearch\Builder\Request\SimpleRequestBuilder as RequestBuilder;
use Gally\Search\Elasticsearch\Request\Container\Configuration\ContainerConfigurationProvider;
use Gally\Search\GraphQl\Type\Definition\SortInputType;
use Gally\Search\Model\Document;
use Gally\Search\Service\GraphQl\FilterManager;
use Gally\Search\Service\SearchContext;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class DocumentDataProvider implements ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
{
    public function __construct(
        private DenormalizerInterface $denormalizer,
        private Pagination $pagination,
        private MetadataRepository $metadataRepository,
        private LocalizedCatalogRepository $catalogRepository,
        private RequestBuilder $requestBuilder,
        private ContainerConfigurationProvider $containerConfigurationProvider,
        private Adapter $adapter,
        private FilterManager $filterManager,
        private SortInputType $sortInputType,
        private CurrentCategoryProvider $currentCategoryProvider,
        private PriceGroupProvider $priceGroupProvider,
        private ReferenceLocationProvider $referenceLocationProvider,
        private SearchContext $searchContext,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Document::class === $resourceClass;
    }

    /**
     * {@inheritDoc}
     */
    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): iterable
    {
        $searchQuery = $context['filters']['search'] ?? null;
        $this->initSearchContext($searchQuery);

        $metadata = $this->metadataRepository->findByEntity($context['filters']['entityType']);
        $localizedCatalog = $this->catalogRepository->findByCodeOrId($context['filters']['localizedCatalog']);

        $containerConfig = $this->containerConfigurationProvider->get(
            $metadata,
            $localizedCatalog,
            $context['filters']['requestType'] ?? null
        );

        $this->filterManager->validateFilters($context, $containerConfig);

        $limit = $this->pagination->getLimit($resourceClass, $operationName, $context);
        $offset = $this->pagination->getOffset($resourceClass, $operationName, $context);

        $request = $this->requestBuilder->create(
            $containerConfig,
            $offset,
            $limit,
            $searchQuery,
            $this->sortInputType->formatSort($containerConfig, $context, $metadata),
            $this->filterManager->transformToGallyFilters(
                $this->filterManager->getFiltersFromContext($context),
                $containerConfig
            ),
            [],
            ($context['need_aggregations'] ?? false) ? [] : null
        );
        $response = $this->adapter->search($request);

        return new Paginator(
            $this->denormalizer,
            $request,
            $response,
            $resourceClass,
            $limit,
            $offset,
            $context
        );
    }

    protected function initSearchContext(?string $searchQuery): void
    {
        $this->searchContext->setCategory($this->currentCategoryProvider->getCurrentCategory());
        $this->searchContext->setSearchQueryText($searchQuery);
        $this->searchContext->setPriceGroup($this->priceGroupProvider->getCurrentPriceGroupId());
        $this->searchContext->setReferenceLocation($this->referenceLocationProvider->getReferenceLocation());
    }
}
