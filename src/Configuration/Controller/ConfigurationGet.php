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

namespace Gally\Configuration\Controller;

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use Gally\Catalog\Repository\CatalogRepository;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Category\Repository\CategoryConfigurationRepository;
use Gally\Category\Repository\CategoryRepository;
use Gally\Configuration\Entity\Configuration;
use Gally\Configuration\State\ConfigurationProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
class ConfigurationGet extends AbstractController
{
    public function __construct(
        private ConfigurationProvider $configurationProvider
    ) {
    }

    public function __invoke(string $path, Request $request): Configuration
    {
        return $this->configurationProvider->get($path);
    }
}
