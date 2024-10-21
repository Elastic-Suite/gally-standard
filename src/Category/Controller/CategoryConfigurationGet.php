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

namespace Gally\Category\Controller;

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use Gally\Catalog\Repository\CatalogRepository;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Category\Repository\CategoryConfigurationRepository;
use Gally\Category\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
class CategoryConfigurationGet extends AbstractController
{
    public function __construct(
        private CategoryConfigurationRepository $configurationRepository,
        private CatalogRepository $catalogRepository,
        private LocalizedCatalogRepository $localizedCatalogRepository,
        private CategoryRepository $categoryRepository,
        private SerializerInterface $serializer,
        private IriConverterInterface $iriConverter,
    ) {
    }

    public function __invoke(string $categoryId, Request $request): string
    {
        $category = $this->categoryRepository->find($categoryId);
        if (!$category) {
            throw new NotFoundHttpException(\sprintf('Category with id %s not found.', $categoryId));
        }

        $catalogId = $request->query->get('catalogId');
        $catalog = $catalogId ? $this->catalogRepository->find($catalogId) : null;
        if ($catalogId && !$catalog) {
            throw new NotFoundHttpException(\sprintf('Catalog with id %d not found.', $catalogId));
        }

        $localizedCatalogId = $request->query->get('localizedCatalogId');
        $localizedCatalog = $localizedCatalogId ? $this->localizedCatalogRepository->find($localizedCatalogId) : null;
        if ($localizedCatalogId && !$localizedCatalog) {
            throw new NotFoundHttpException(\sprintf('Localized catalog with id %d not found.', $localizedCatalogId));
        }

        $config = $this->configurationRepository->findOneMergedByContext($category, $catalog, $localizedCatalog);

        if (!$config) {
            throw new NotFoundHttpException('Not found');
        }

        $resources = [
            $this->iriConverter->getIriFromResource($config, UrlGeneratorInterface::ABS_PATH),
            $this->iriConverter->getIriFromResource($config->getCategory(), UrlGeneratorInterface::ABS_PATH),
        ];
        $request->attributes->set('_resources', $request->attributes->get('_resources', []) + $resources);

        $format = $request->getRequestFormat() ?? 'jsonld';

        return $this->serializer->serialize($config, $format);
    }
}
