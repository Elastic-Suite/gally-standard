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

namespace Gally\Category\Service;

use Gally\Catalog\Entity\Catalog;
use Gally\Catalog\Entity\LocalizedCatalog;
use Gally\Catalog\Repository\CatalogRepository;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Catalog\Service\DefaultCatalogProvider;
use Gally\Category\Entity\Category;
use Gally\Category\Entity\CategoryTree;
use Gally\Category\Repository\CategoryConfigurationRepository;
use Gally\Category\Repository\CategoryRepository;
use Gally\Metadata\Repository\MetadataRepository;
use Gally\Product\Entity\Product;
use Gally\Search\Elasticsearch\Adapter;
use Gally\Search\Elasticsearch\Builder\Request\SimpleRequestBuilder as RequestBuilder;
use Gally\Search\Elasticsearch\Request\Container\Configuration\ContainerConfigurationProvider;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CategoryTreeBuilder
{
    private array $productCategoryCount = [];

    public function __construct(
        private CatalogRepository $catalogRepository,
        private LocalizedCatalogRepository $localizedCatalogRepository,
        private CategoryRepository $categoryRepository,
        private CategoryConfigurationRepository $categoryConfigurationRepository,
        private DefaultCatalogProvider $defaultCatalogProvider,
        private RequestBuilder $requestBuilder,
        private MetadataRepository $metadataRepository,
        private ContainerConfigurationProvider $containerConfigurationProvider,
        private Adapter $adapter,
    ) {
    }

    public function buildTree(?int $catalogId, ?int $localizedCatalogId): CategoryTree
    {
        $localizedCatalog = $localizedCatalogId ? $this->localizedCatalogRepository->find($localizedCatalogId) : null;
        if ($localizedCatalogId && !$localizedCatalog) {
            throw new NotFoundHttpException(\sprintf('Localized catalog with id %d not found.', $localizedCatalogId));
        }

        $catalog = $catalogId
            ? $this->catalogRepository->find($catalogId)
            : $localizedCatalog?->getCatalog();
        if ($catalogId && !$catalog) {
            throw new NotFoundHttpException(\sprintf('Catalog with id %d not found.', $catalogId));
        }

        $sortedCategories = $this->getSortedCategories($catalog, $localizedCatalog);
        if ($localizedCatalog) {
            $this->productCategoryCount = $this->getCategoryProductCount($localizedCatalog);
        }

        return new CategoryTree(
            $catalogId,
            $localizedCatalogId,
            $this->buildCategoryTree($sortedCategories, 1, 'root', null !== $localizedCatalogId),
        );
    }

    private function getSortedCategories(?Catalog $catalog, ?LocalizedCatalog $localizedCatalog): array
    {
        $shouldDisplayInactive = null === $localizedCatalog;

        if (!$localizedCatalog) {
            if (!$catalog) {
                $localizedCatalog = $this->defaultCatalogProvider->getDefaultLocalizedCatalog();
            }
        }

        $categoryConfigurations = $catalog
            ? $this->categoryConfigurationRepository->findMergedByContext($catalog, $localizedCatalog)
            : $this->categoryConfigurationRepository->findAllMerged($localizedCatalog);

        $sortedCategories = [];
        $categories = $this->categoryRepository->findAllIndexedById();

        foreach ($categoryConfigurations as $categoryConfigurationData) {
            $category = $categories[$categoryConfigurationData['category_id']];
            $categoryConfiguration = new Category\Configuration();
            $categoryConfiguration->setCategory($category);
            $categoryConfiguration->setName($categoryConfigurationData['name']);
            $categoryConfiguration->setIsVirtual((bool) $categoryConfigurationData['isVirtual']);
            $categoryConfiguration->setVirtualRule($categoryConfigurationData['virtualRule']);
            $categoryConfiguration->setDefaultSorting($categoryConfigurationData['defaultSorting']);
            $categoryConfiguration->setUseNameInProductSearch((bool) $categoryConfigurationData['useNameInProductSearch']);
            $categoryConfiguration->setIsActive((bool) $categoryConfigurationData['isActive']);

            if (!$shouldDisplayInactive && !$categoryConfiguration->getIsActive()) {
                continue;
            }

            $level = $category->getLevel();
            if (!\array_key_exists($level, $sortedCategories)) {
                $sortedCategories[$level] = [];
            }

            $parent = $category->getParentId() ?: 'root';
            $parentCategories = &$sortedCategories[$level];
            if (!\array_key_exists($parent, $parentCategories)) { // @phpstan-ignore-line
                $parentCategories[$parent] = [];
            }

            $parentCategories[$parent][] = $categoryConfiguration;
        }

        return $sortedCategories;
    }

    private function buildCategoryTree(
        array $sortedCategories,
        int $level = 1,
        string $parentId = 'root',
        bool $includeProductCount = false,
    ): array {
        $tree = [];

        foreach ($sortedCategories[$level][$parentId] ?? [] as $categoryConfiguration) {
            $tree[] = $this->buildCategoryNode($sortedCategories, $categoryConfiguration, $includeProductCount);
        }

        return $tree;
    }

    private function buildCategoryNode(
        array $sortedCategories,
        Category\Configuration $categoryConfiguration,
        bool $includeProductCount,
    ): array {
        $category = $categoryConfiguration->getCategory();
        $children = $this->buildCategoryTree(
            $sortedCategories,
            $category->getLevel() + 1,
            $category->getId(),
            $includeProductCount
        );

        $node = [
            'id' => $category->getId(),
            'name' => $categoryConfiguration->getName(),
            'level' => $category->getLevel(),
            'path' => $category->getPath(),
            'isVirtual' => $categoryConfiguration->getIsVirtual(),
        ];

        if ($includeProductCount) {
            $node['count'] = $this->productCategoryCount[$category->getId()] ?? 0;
        }

        if (!empty($children)) {
            $node['children'] = $children;
        }

        return $node;
    }

    private function getCategoryProductCount(LocalizedCatalog $localizedCatalog): array
    {
        $metadata = $this->metadataRepository->findByRessourceClass(Product::class);
        $containerConfig = $this->containerConfigurationProvider->get(
            $metadata,
            $localizedCatalog,
            'product_category_count'
        );
        $request = $this->requestBuilder->create($containerConfig, 0, 0);
        $response = $this->adapter->search($request);

        $count = [];

        $aggregations = $response->getAggregations();
        $categoryAggregation = \array_key_exists('category.id', $aggregations)
            ? $aggregations['category.id']->getValues()
            : [];
        foreach ($categoryAggregation as $option) {
            $count[$option->getKey()] = $option->getCount();
        }

        return $count;
    }
}
