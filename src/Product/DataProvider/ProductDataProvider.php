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

namespace Gally\Product\DataProvider;

use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\Pagination;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Gally\Catalog\Model\LocalizedCatalog;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Category\Model\Category;
use Gally\Category\Repository\CategoryConfigurationRepository;
use Gally\Category\Service\CurrentCategoryProvider;
use Gally\Entity\Service\PriceGroupProvider;
use Gally\Entity\Service\ReferenceLocationProvider;
use Gally\Metadata\Repository\MetadataRepository;
use Gally\Product\GraphQl\Type\Definition\SortInputType;
use Gally\Product\Model\Product;
use Gally\Product\Service\GraphQl\FilterManager;
use Gally\Search\DataProvider\Paginator;
use Gally\Search\Elasticsearch\Adapter;
use Gally\Search\Elasticsearch\Builder\Request\SimpleRequestBuilder as RequestBuilder;
use Gally\Search\Elasticsearch\Request\Container\Configuration\ContainerConfigurationProvider;
use Gally\Search\Service\SearchContext;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Serializer;

class ProductDataProvider implements ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
{
    public function __construct(
        private DenormalizerInterface $denormalizer,
        private Pagination $pagination,
        private MetadataRepository $metadataRepository,
        private LocalizedCatalogRepository $localizedCatalogRepository,
        private RequestBuilder $requestBuilder,
        private ContainerConfigurationProvider $containerConfigurationProvider,
        private Adapter $adapter,
        private SortInputType $sortInputType,
        private FilterManager $filterManager,
        private CurrentCategoryProvider $currentCategoryProvider,
        private PriceGroupProvider $priceGroupProvider,
        private ReferenceLocationProvider $referenceLocationProvider,
        private SearchContext $searchContext,
        private EntityManagerInterface $entityManager,
        private Serializer $serializer,
        private CategoryConfigurationRepository $categoryConfigurationRepository
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Product::class === $resourceClass;
    }

    /**
     * {@inheritDoc}
     *
     * @throws ResourceClassNotFoundException
     */
    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): iterable
    {
        $searchQuery = $context['filters']['search'] ?? null;
        $currentCategoryId = $context['filters']['currentCategoryId'] ?? null;
        if ($currentCategoryId) {
            $this->currentCategoryProvider->setCurrentCategory($currentCategoryId);
        }

        $this->initSearchContext($searchQuery);

        // TODO Supposed to be pulled from header.
        $localizedCatalogCode = $context['filters']['localizedCatalog'];
        $metadata = $this->metadataRepository->findByRessourceClass($resourceClass);
        $localizedCatalog = $this->localizedCatalogRepository->findByCodeOrId($localizedCatalogCode);

        $currentCategoryConfiguration = $this->emulateCategoryConfigurationStart($context, $localizedCatalog);

        $containerConfig = $this->containerConfigurationProvider->get(
            $metadata,
            $localizedCatalog,
            $context['filters']['requestType']
        );

        $this->filterManager->validateFilters($context, $containerConfig);
        $this->sortInputType->validateSort($context);

        $limit = $this->pagination->getLimit($resourceClass, $operationName, $context);
        $offset = $this->pagination->getOffset($resourceClass, $operationName, $context);

        // Get query filter and set current category.
        $queryFilter = $this->filterManager->transformToGallyFilters(
            $this->filterManager->getQueryFilterFromContext($context),
            $containerConfig
        );

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
            $queryFilter,
            ($context['need_aggregations'] ?? false) ? [] : null
        );
        $response = $this->adapter->search($request);

        $this->emulateCategoryConfigurationStop($context, $currentCategoryConfiguration);

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

    protected function isPreviewMode(array $context): bool
    {
        return 'searchPreview' === $context['graphql_operation_name'];
    }

    /**
     * Remove the category configuration with the scope $localizedCatalog to replace it by the configuration get from GraphQL args.
     */
    protected function emulateCategoryConfigurationStart(array $context, LocalizedCatalog $localizedCatalog): ?Category\Configuration
    {
        if (!$this->isPreviewMode($context)) {
            return null;
        }

        $currentCategoryConfigurationData = isset($context['filters']['currentCategoryConfiguration']) ? json_decode($context['filters']['currentCategoryConfiguration'], true) : null;
        $currentCategoryConfiguration = $currentCategoryConfigurationData ? $this->serializer->denormalize($currentCategoryConfigurationData, Category\Configuration::class, 'jsonld') : null;
        if ($currentCategoryConfiguration instanceof Category\Configuration) {
            $this->entityManager->beginTransaction();

            $prevCatConf = $this->categoryConfigurationRepository->findOneBy([
                'catalog' => $localizedCatalog->getCatalog(),
                'localizedCatalog' => $localizedCatalog,
                'category' => $currentCategoryConfiguration->getCategory(),
            ]);
            if ($prevCatConf instanceof Category\Configuration) {
                $this->entityManager->remove($prevCatConf);
                $this->entityManager->flush();
            }

            $currentCategoryConfiguration->setLocalizedCatalog($localizedCatalog);
            $currentCategoryConfiguration->setCatalog($localizedCatalog->getCatalog());
            $this->entityManager->persist($currentCategoryConfiguration);
            $this->entityManager->flush();
        }

        return $currentCategoryConfiguration;
    }

    protected function emulateCategoryConfigurationStop(array $context, ?Category\Configuration $currentCategoryConfiguration): void
    {
        if ($this->isPreviewMode($context) && $currentCategoryConfiguration instanceof Category\Configuration) {
            $this->entityManager->rollback();
        }
    }
}
