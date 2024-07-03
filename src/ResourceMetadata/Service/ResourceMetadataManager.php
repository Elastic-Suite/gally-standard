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

namespace Gally\ResourceMetadata\Service;

use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Gally\Exception\LogicException;

/**
 *  Allows to manage gally attributes on ApiResources.
 */
class ResourceMetadataManager
{
    public const RESOURCE_METADATA_PATH_ROOT = 'gally';

    public function __construct(
        private ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
    ) {
    }

    public function getIndex(ResourceMetadataCollection $resourceMetadataCollection): ?string
    {
        return $this->getResourceMetadataValue($resourceMetadataCollection, 'index');
    }

    public function getMetadataEntity(ResourceMetadataCollection $resourceMetadataCollection): ?string
    {
        return $this->getResourceMetadataValue($resourceMetadataCollection, 'metadata/entity');
    }

    public function getStitchingProperty(ResourceMetadataCollection $resourceMetadataCollection): ?string
    {
        return $this->getResourceMetadataValue($resourceMetadataCollection, 'stitching/property');
    }

    public function getCacheTagResourceClasses(ResourceMetadataCollection $resourceMetadataCollection): ?array
    {
        return $this->getResourceMetadataValue($resourceMetadataCollection, 'cache_tag/resource_classes');
    }

    /**
     * Get resource metadata value.
     *
     * @param ResourceMetadataCollection $resourceMetadataCollection  resource metadata collection
     * @param string                     $path                        path of the metadata value node to get from gally node, key levels separated by a '/'
     *                                                                (example: stitching/property)
     */
    public function getResourceMetadataValue(ResourceMetadataCollection $resourceMetadataCollection, string $path): mixed
    {
        $path = explode('/', $path);

        foreach ($resourceMetadataCollection as $resourceMetadata) {
            $value = $resourceMetadata->getExtraProperties()[self::RESOURCE_METADATA_PATH_ROOT] ?? false;
            if ($value) {
                break;
            }
        }

        foreach ($path as $key) {
            if (!isset($value[$key])) {
                $value = null;
                break;
            }
            $value = $value[$key];
        }

        return $value;
    }

    public function getOperation(string $resourceClass, ?string $operationClass = null): Operation
    {
        $resourceMetadataCollection = $this->resourceMetadataCollectionFactory->create($resourceClass);
        $operationClass = $operationClass ?? Get::class;

        foreach ($resourceMetadataCollection as $resourceMetadata) {
            if ($resourceMetadata->getClass() === $resourceClass) {
                foreach ($resourceMetadata->getOperations() as $operation) {
                    if ($operation instanceof $operationClass) {
                        return $operation;
                    }
                }
            }
        }

        throw new LogicException("'{$operationClass}' operation does not exist for the resource '{$resourceClass}'.");
    }
}
