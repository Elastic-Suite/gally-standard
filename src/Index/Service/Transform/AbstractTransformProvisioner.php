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

namespace Gally\Index\Service\Transform;

use Gally\Catalog\Entity\LocalizedCatalog;
use Gally\Index\Repository\Transform\TransformRepositoryInterface;

abstract class AbstractTransformProvisioner implements TransformProvisionerInterface
{
    public function __construct(protected readonly TransformRepositoryInterface $transformRepository)
    {
    }

    /**
     * Whether the transform is actually running, as opposed to missing, stopped, or auto-disabled by OpenSearch after a failure.
     */
    public function isHealthy(LocalizedCatalog $localizedCatalog): bool
    {
        $transformId = $this->getTransformId($localizedCatalog);

        try {
            $explain = $this->transformRepository->explain($transformId);
            $status = $explain[$transformId]['transform_metadata']['status'] ?? null;
        } catch (\Exception) {
            return false;
        }

        return \in_array($status, ['started', 'init'], true);
    }

    public function remove(LocalizedCatalog $localizedCatalog): void
    {
        $transformId = $this->getTransformId($localizedCatalog);

        try {
            $this->transformRepository->stop($transformId);
        } catch (\Exception) {
            // Already stopped or never started.
        }

        try {
            $this->transformRepository->delete($transformId);
        } catch (\Exception) {
            // Nothing to delete.
        }
    }
}
