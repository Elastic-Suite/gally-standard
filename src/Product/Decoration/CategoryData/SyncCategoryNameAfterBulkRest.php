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

namespace Gally\Product\Decoration\CategoryData;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use Gally\Index\DataPersister\DocumentDataPersister;
use Gally\Index\Model\IndexDocument;
use Gally\Index\Repository\Index\IndexRepositoryInterface;
use Gally\Product\Service\CategoryNameUpdater;

class SyncCategoryNameAfterBulkRest implements DataPersisterInterface
{
    public function __construct(
        private DocumentDataPersister $decorated,
        private IndexRepositoryInterface $indexRepository,
        private CategoryNameUpdater $categoryNameUpdater
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

        return $index;
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function remove($data)
    {
        $this->decorated->remove($data);
    }

    /**
     * {@inheritdoc}
     */
    public function supports($data): bool
    {
        return $this->decorated->supports($data);
    }
}
