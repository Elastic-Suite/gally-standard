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

namespace Gally\Category\Decoration;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Gally\Category\Exception\SyncCategoryException;
use Gally\Category\Service\CategoryProductPositionManager;
use Gally\Category\Service\CategorySynchronizer;
use Gally\Index\Api\IndexSettingsInterface;
use Gally\Index\Model\IndexDocument;
use Gally\Index\Repository\Index\IndexRepositoryInterface;

class SyncCategoryDataAfterBulkRest implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $decorated,
        private CategorySynchronizer $synchronizer,
        private IndexSettingsInterface $indexSettings,
        private IndexRepositoryInterface $indexRepository,
        private CategoryProductPositionManager $categoryProductPositionManager,
    ) {
    }

    /**
     * @param IndexDocument $data
     */
    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): ?IndexDocument
    {
        if ($operation instanceof DeleteOperationInterface) {
            return $this->decorated->process($data, $operation, $uriVariables, $context);
        }

        $this->decorated->process($data, $operation, $uriVariables, $context);
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

        return $data;
    }
}
