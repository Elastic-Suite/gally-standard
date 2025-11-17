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

namespace Gally\Product\Decoration\CategoryData;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Gally\Index\Entity\IndexDocument;
use Gally\Index\Repository\Index\IndexRepositoryInterface;
use Gally\Index\State\DocumentProcessor;
use Gally\Product\Service\CategoryNameUpdater;

class SyncCategoryNameAfterBulkRest implements ProcessorInterface
{
    public function __construct(
        private DocumentProcessor $decorated,
        private IndexRepositoryInterface $indexRepository,
        private CategoryNameUpdater $categoryNameUpdater,
    ) {
    }

    /**
     * @param IndexDocument $data
     */
    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        $this->decorated->process($data, $operation, $uriVariables, $context);
        if ($operation instanceof DeleteOperationInterface) {
            return;
        }

        $index = $this->indexRepository->findByName($data->getIndexName());

        if (null !== $index->getEntityType()) {
            if ('category' === $index->getEntityType()) {
                // Handle category name change ?
            }

            if (('product' === $index->getEntityType()) && $index->getLocalizedCatalog()) {
                // Handle copying category.name to category._name
                $this->categoryNameUpdater->updateCategoryNames(
                    $index,
                    array_map(fn ($document) => json_decode($document, true), $data->getDocuments())
                );
            }
        }
    }
}
