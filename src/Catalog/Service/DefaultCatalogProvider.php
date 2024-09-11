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

namespace Gally\Catalog\Service;

use Gally\Catalog\Exception\NoCatalogException;
use Gally\Catalog\Entity\LocalizedCatalog;
use Gally\Catalog\Repository\LocalizedCatalogRepository;

class DefaultCatalogProvider
{
    public function __construct(
        private LocalizedCatalogRepository $localizedCatalogRepository
    ) {
    }

    public function getDefaultLocalizedCatalog(): LocalizedCatalog
    {
        $catalog = $this->localizedCatalogRepository->findOneBy([], ['isDefault' => 'DESC', 'id' => 'ASC']);
        if (null === $catalog) {
            throw new NoCatalogException();
        }

        return $catalog;
    }
}
