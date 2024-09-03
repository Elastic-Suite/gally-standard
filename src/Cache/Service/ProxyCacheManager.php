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

namespace Gally\Cache\Service;

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Gally\ResourceMetadata\Service\ResourceMetadataManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ProxyCacheManager
{
    public function __construct(
        private RequestStack $requestStack,
        private ResourceMetadataManager $resourceMetadataManager,
        private ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
        private IriConverterInterface $iriConverter,
    ) {
    }

    /**
     * Add $resources in attribute '_resources' request.
     * The attribute resource is used to generate Cache-tags header @see \ApiPlatform\Core\HttpCache\EventListener\AddTagsListener::onKernelResponse.
     */
    public function addResourcesToRequest(array $resources, ?Request $request): void
    {
        if (null === $request) {
            $request = $this->requestStack->getCurrentRequest();
        }

        $request->attributes->set('_resources', $request->attributes->get('_resources', []) + $resources);
    }

    /**
     * Allows to add cache tags related to the resource classes found in the ApiResource metadata node gally/cache_tag/resource_classes in $resourceClass.
     */
    public function addCacheTagResourceCollection(string $resourceClass, ?Request $request = null): void
    {
        $resourceMetadataCollection = $this->resourceMetadataCollectionFactory->create($resourceClass);
        $resourceClasses = $this->resourceMetadataManager->getCacheTagResourceClasses($resourceMetadataCollection);

        if (null !== $resourceClasses) {
            $resources = [];
            foreach ($resourceClasses as $class) {
                $iri = $this->iriConverter->getIriFromResource($class, UrlGeneratorInterface::ABS_PATH, new GetCollection());
                $resources[$iri] = $iri;
            }

            $this->addResourcesToRequest($resources, $request);
        }
    }
}
