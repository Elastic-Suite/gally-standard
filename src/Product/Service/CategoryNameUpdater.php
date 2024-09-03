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

namespace Gally\Product\Service;

use Gally\Catalog\Model\LocalizedCatalog;
use Gally\Category\Model\Category\Configuration as CategoryConfiguration;
use Gally\Category\Repository\CategoryConfigurationRepository;
use Gally\Index\Dto\Bulk;
use Gally\Index\Model\Index;
use Gally\Index\Repository\Index\IndexRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Product categories name updater.
 */
class CategoryNameUpdater
{
    private array $categoriesConfigCache = [];

    public function __construct(
        private CategoryConfigurationRepository $categoryConfigurationRepository,
        private IndexRepositoryInterface $indexRepository,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Update category names based on their "useNameInProductSearch" settings
     * on a given set of product bulk data for a given index.
     *
     * @param Index $index           Product index
     * @param array $productBulkData Product index bulkIndex data
     */
    public function updateCategoryNames(Index $index, array $productBulkData = []): void
    {
        $productDataUpdates = [];

        $localizedCatalogConfig = $this->getUseNameInProductSearchCategories($index->getLocalizedCatalog());
        if (!empty($localizedCatalogConfig)) {
            foreach ($productBulkData as &$productData) {
                $updatedData = $this->prepareProductData($productData, $localizedCatalogConfig);
                if (!empty($updatedData)) {
                    $updatedData['id'] = $productData['id'];
                    $productDataUpdates[$productData['id']] = $updatedData;
                }
            }
        }

        if (!empty($productDataUpdates)) {
            $this->applyUpdates($index, $productDataUpdates);
        }
    }

    /**
     * Retrieve localized catalog configuration for using the categories name in search.
     * Returns array whose keys are the category id and the value the localized category name,
     * only for categories whose name is supposed to be used in product search.
     *
     * @param LocalizedCatalog $localizedCatalog Localized catalog
     */
    private function getUseNameInProductSearchCategories(LocalizedCatalog $localizedCatalog): array
    {
        if (!\array_key_exists($localizedCatalog->getId(), $this->categoriesConfigCache)) {
            $this->categoriesConfigCache[$localizedCatalog->getId()] = [];
            $localizedCatalogConfig = &$this->categoriesConfigCache[$localizedCatalog->getId()];

            /** @var CategoryConfiguration[] $categoryConfigs */
            $categoryConfigs = $this->categoryConfigurationRepository->findMergedByContext($localizedCatalog->getCatalog(), $localizedCatalog);

            foreach ($categoryConfigs as $categoryConfig) {
                if ($categoryConfig['useNameInProductSearch'] ?? true) {
                    $localizedCatalogConfig[$categoryConfig['category_id']] = $categoryConfig['name'];
                }
            }
        }

        return $this->categoriesConfigCache[$localizedCatalog->getId()];
    }

    /**
     * Prepare product data update.
     *
     * @param array $productData            Bulk product data
     * @param array $localizedCatalogConfig Localized catalog config for category names
     *
     * @return array An empty array if nothing to change, or an updated category data structure
     */
    private function prepareProductData(array &$productData, array &$localizedCatalogConfig): array
    {
        $updatedCategoriesData = [];
        $hasUpdatedData = false;

        if (\array_key_exists('category', $productData)) {
            $categoriesData = &$productData['category'];

            foreach ($categoriesData as $categoryData) {
                $updatedCategoryData = $categoryData;
                if (\array_key_exists('id', $categoryData) && \array_key_exists($categoryData['id'], $localizedCatalogConfig)) {
                    // Assume the product bulk data is "fresher" than the local category data for the name.
                    $categoryName = $categoryData['name'] ?? $localizedCatalogConfig[$categoryData['id']];
                    // Also copies the category name if missing from product bulk data.
                    $updatedCategoryData = $categoryData + ['name' => $categoryName, '_name' => $categoryName];
                    $hasUpdatedData = true;
                }
                $updatedCategoriesData[] = $updatedCategoryData;
            }
        }

        return $hasUpdatedData ? ['category' => $updatedCategoriesData] : [];
    }

    /**
     * Apply product updates on a given product index.
     *
     * @param Index $index              Product index
     * @param array $productDataUpdates Product data updates as an array whose keys are product ids
     */
    private function applyUpdates(Index $index, array &$productDataUpdates): void
    {
        $request = new Bulk\Request();
        $request->updateDocuments($index, $productDataUpdates);
        $response = $this->indexRepository->bulk($request);
        if ($response->hasErrors()) {
            foreach ($response->aggregateErrorsByReason() as $error) {
                $sampleDocumentIds = implode(', ', \array_slice($error['document_ids'], 0, 10));
                $this->logger->error(\sprintf(
                    'Bulk %s operation failed %d times in index %s.',
                    $error['operation'],
                    $error['count'],
                    $error['index']
                ));
                $this->logger->error(\sprintf('Error (%s) : %s.', $error['error']['type'], $error['error']['reason']));
                $this->logger->error(\sprintf('Failed doc ids sample : %s.', $sampleDocumentIds));
            }
        }
    }
}
