<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @package   Elasticsuite
 * @author    ElasticSuite Team <elasticsuite@smile.fr>
 * @copyright 2022 Smile
 * @license   Licensed to Smile-SA. All rights reserved. No warranty, explicit or implicit, provided.
 *            Unauthorized copying of this file, via any medium, is strictly prohibited.
 */

declare(strict_types=1);

namespace Elasticsuite\Category\Controller;

use Elasticsuite\Catalog\Repository\CatalogRepository;
use Elasticsuite\Catalog\Repository\LocalizedCatalogRepository;
use Elasticsuite\Category\Model\Category\ProductMerchandising;
use Elasticsuite\Category\Repository\CategoryRepository;
use Elasticsuite\Category\Service\CategoryProductPositionManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

#[AsController]
class CategoryProductPositionSave extends AbstractController
{
    public function __construct(
        private CategoryRepository $categoryRepository,
        private CatalogRepository $catalogRepository,
        private LocalizedCatalogRepository $localizedCatalogRepository,
        private CategoryProductPositionManager $categoryProductPositionManager,
    ) {
    }

    public function __invoke(string $categoryId, Request $request): ProductMerchandising
    {
        $body = json_decode($request->getContent(), true);
        $category = $this->categoryRepository->find($categoryId);
        if (!$category) {
            throw new BadRequestHttpException(sprintf('Category with id %s not found.', $categoryId));
        }

        $catalogId = $body['catalogId'] ?? null;
        $catalog = $catalogId ? $this->catalogRepository->find($catalogId) : null;
        if ($catalogId && !$catalog) {
            throw new BadRequestHttpException(sprintf('Catalog with id %d not found.', $catalogId));
        }

        $localizedCatalogId = $body['localizedCatalogId'] ?? null;
        $localizedCatalog = $localizedCatalogId ? $this->localizedCatalogRepository->find($localizedCatalogId) : null;
        if ($localizedCatalogId && !$localizedCatalog) {
            throw new BadRequestHttpException(sprintf('Localized catalog with id %d not found.', $localizedCatalogId));
        }

        $positionsJson = $body['positions'] ?? null;
        if (null === $positionsJson) {
            throw new BadRequestHttpException('Positions are empty.');
        }

        $positions = json_decode($positionsJson, true);
        if (false === $positions || null === $positions) {
            throw new BadRequestHttpException('JSON positions object cannot be decoded.');
        }

        $this->categoryProductPositionManager->savePositions(
            $positions,
            $category,
            $catalog,
            $localizedCatalog
        );

        $productMerchandising = new ProductMerchandising();
        $productMerchandising->setId(0);

        return $productMerchandising;
    }
}