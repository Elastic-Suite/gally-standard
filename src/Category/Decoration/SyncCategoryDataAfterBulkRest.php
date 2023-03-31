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

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use Gally\Category\Exception\SyncCategoryException;
use Gally\Category\Service\CategoryProductPositionManager;
use Gally\Category\Service\CategorySynchronizer;
use Gally\Index\Api\IndexSettingsInterface;
use Gally\Index\Model\IndexDocument;
use Gally\Index\Repository\Index\IndexRepositoryInterface;

class SyncCategoryDataAfterBulkRest implements DataPersisterInterface
{
    public function __construct(
        private DataPersisterInterface $decorated,
        private CategorySynchronizer $synchronizer,
        private IndexSettingsInterface $indexSettings,
        private IndexRepositoryInterface $indexRepository,
        private CategoryProductPositionManager $categoryProductPositionManager,
    ) {
    }

    /**
     * {@inheritdoc}
     *
     * @return object|void
     */
    public function persist($data)
    {
        /** @var IndexDocument $data */
        $this->decorated->persist($data);
        $index = $this->indexRepository->findByName($data->getIndexName());

        if (null !== $index->getEntityType() && $this->indexSettings->isInstalled($index)) { // Don't synchronize if index is not installed
            if ('category' === $index->getEntityType()) { // Synchronize sql data for category entity
                $this->indexRepository->refresh($index->getName()); // Force refresh to avoid missing data
                try {
                    $this->synchronizer->synchronize(
                        $index,
                        array_map(fn ($document) => json_decode($document, true), $data->getDocuments())
                    );
                } catch (SyncCategoryException) {
                    // If sync failed, retry sync once, then log the error.
                    $this->synchronizer->synchronize(
                        $index,
                        array_map(fn ($document) => json_decode($document, true), $data->getDocuments())
                    );
                }
            }

            if ('product' === $index->getEntityType()) {
                $this->indexRepository->refresh($index->getName()); // Force refresh to avoid missing data
                $this->categoryProductPositionManager->reindexPositionsByIndex(
                    $index,
                    array_column($data->getDocuments(), 'id')
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function remove($data)
    {
        return $this->decorated->remove($data);
    }

    /**
     * {@inheritdoc}
     */
    public function supports($data): bool
    {
        return $this->decorated->supports($data);
    }
}
