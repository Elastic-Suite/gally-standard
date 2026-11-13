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

interface TransformProvisionerInterface
{
    public function getType(): string;

    public function getTransformId(LocalizedCatalog $localizedCatalog): string;

    /**
     * Returns the transform id, or null when nothing was provisioned for this catalog (e.g. feature disabled).
     */
    public function createOrUpdate(LocalizedCatalog $localizedCatalog): ?string;
}
