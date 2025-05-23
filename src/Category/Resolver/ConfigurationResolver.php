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

namespace Gally\Category\Resolver;

use ApiPlatform\GraphQl\Resolver\QueryItemResolverInterface;
use Gally\Catalog\Repository\CatalogRepository;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Category\Entity\Category\Configuration;
use Gally\Category\Repository\CategoryConfigurationRepository;
use Gally\Category\Repository\CategoryRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ConfigurationResolver implements QueryItemResolverInterface
{
    public function __construct(
        private CategoryConfigurationRepository $configurationRepository,
        private CatalogRepository $catalogRepository,
        private LocalizedCatalogRepository $localizedCatalogRepository,
        private CategoryRepository $categoryRepository,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(?object $item, array $context): Configuration
    {
        $categoryId = $context['args']['categoryId'];
        $category = $this->categoryRepository->find($categoryId);
        if (!$category) {
            throw new NotFoundHttpException(\sprintf('Category with id %s not found.', $categoryId));
        }

        $catalogId = $context['args']['catalogId'] ?? null;
        $catalog = $catalogId ? $this->catalogRepository->find($catalogId) : null;
        if ($catalogId && !$catalog) {
            throw new NotFoundHttpException(\sprintf('Catalog with id %d not found.', $catalogId));
        }

        $localizedCatalogId = $context['args']['localizedCatalogId'] ?? null;
        $localizedCatalog = $localizedCatalogId ? $this->localizedCatalogRepository->find($localizedCatalogId) : null;
        if ($localizedCatalogId && !$localizedCatalog) {
            throw new NotFoundHttpException(\sprintf('Localized catalog with id %d not found.', $localizedCatalogId));
        }

        $config = $this->configurationRepository->findOneMergedByContext($category, $catalog, $localizedCatalog);
        if (!$config) {
            throw new NotFoundHttpException('Not found');
        }

        return $config;
    }
}
