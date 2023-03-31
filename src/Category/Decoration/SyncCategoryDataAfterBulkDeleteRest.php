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

namespace Gally\Category\Decoration;

use Gally\Category\Exception\SyncCategoryException;
use Gally\Category\Repository\CategoryProductMerchandisingRepository;
use Gally\Category\Service\CategorySynchronizer;
use Gally\Index\Api\IndexSettingsInterface;
use Gally\Index\Controller\RemoveIndexDocument;
use Gally\Index\Repository\Index\IndexRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;

class SyncCategoryDataAfterBulkDeleteRest extends RemoveIndexDocument
{
    public function __construct(
        private RemoveIndexDocument $decorated,
        private CategorySynchronizer $synchronizer,
        private IndexSettingsInterface $indexSettings,
        private IndexRepositoryInterface $indexRepository,
        private CategoryProductMerchandisingRepository $categoryProductMerchandisingRepository,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(string $indexName, Request $request)
    {
        $this->decorated->__invoke($indexName, $request);
        $index = $this->indexRepository->findByName($indexName);

        if (null !== $index->getEntityType() && $this->indexSettings->isInstalled($index)) { // Don't synchronize if index is not installed
            if ('category' === $index->getEntityType()) { // Synchronize sql data for category entity
                $this->indexRepository->refresh($index->getName()); // Force refresh to avoid missing data
                try {
                    $this->synchronizer->synchronize($index);
                } catch (SyncCategoryException) {
                    // If sync failed, retry sync once, then log the error.
                    $this->synchronizer->synchronize($index);
                }
            }

            if ('product' === $index->getEntityType()) {
                // Todo: For the moment we remove only values in the scope localized catalog, the others scopes will be managed in ticket ESPP-437.
                $body = json_decode($request->getContent(), true);
                $this->categoryProductMerchandisingRepository->removeByProductIdAndLocalizedCatalog(
                    $body['document_ids'] ?? [],
                    $index->getLocalizedCatalog()
                );
            }
        }
    }
}
