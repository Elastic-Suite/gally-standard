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

use Elasticsuite\Category\Repository\CategoryConfigurationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[AsController]
class CategoryConfigurationGet extends AbstractController
{
    public function __construct(
        private CategoryConfigurationRepository $configurationRepository,
        private RequestStack $requestStack
    ) {
    }

    public function __invoke(string $categoryId)
    {
        $request = $this->requestStack->getCurrentRequest();
        $catalogId = $request->query->get('catalogId');
        $catalogId = $catalogId ? (int) $catalogId : null;
        $localizedCatalogId = (int) $request->query->get('localizedCatalogId');
        $localizedCatalogId = $localizedCatalogId ? (int) $localizedCatalogId : null;
        $config = $this->configurationRepository->findByContext($categoryId, $catalogId, $localizedCatalogId);
        if (!$config) {
            throw new NotFoundHttpException('Not found');
        }

        return $config;
    }
}