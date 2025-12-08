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

namespace Gally\Fixture\Service;

use Gally\Catalog\Entity\LocalizedCatalog;

trait GetLocalizedCatalogs
{
    /**
     * Get all localized catalogs or a specific one based on an identifier.
     *
     * @param int|string|null $localizedCatalogIdentifier Catalog identifier (code or id)
     *
     * @return LocalizedCatalog[]
     */
    protected function getLocalizedCatalogs(int|string|null $localizedCatalogIdentifier = null): array
    {
        $localizedCatalogs = [];

        if (null !== $localizedCatalogIdentifier) {
            $localizedCatalogs[] = $this->localizedCatalogRepository->findByCodeOrId($localizedCatalogIdentifier);
        } else {
            $localizedCatalogs = $this->localizedCatalogRepository->findAll();
        }

        return $localizedCatalogs;
    }
}
