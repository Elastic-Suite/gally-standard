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

namespace Gally\Catalog\DataProvider\Source;

use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use Gally\Catalog\Model\Source\LocalizedCatalogGroupOption;
use Gally\Catalog\Repository\CatalogRepository;

class LocalizedCatalogGroupOptionDataProvider implements ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
{
    public function __construct(
        private CatalogRepository $catalogRepository
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return LocalizedCatalogGroupOption::class === $resourceClass;
    }

    /**
     * {@inheritDoc}
     */
    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): array
    {
        $groupOptions = [];
        foreach ($this->catalogRepository->findAll() as $catalog) {
            $groupOption['value'] = $groupOption['id'] = $catalog->getCode();
            $groupOption['label'] = $catalog->getName();
            $options = [];
            foreach ($catalog->getLocalizedCatalogs() as $localizedCatalog) {
                $option['value'] = "localized_catalogs/{$localizedCatalog->getId()}";
                $option['label'] = $localizedCatalog->getName();
                $options[] = $option;
            }
            $groupOption['options'] = $options;
            $groupOptions[] = $groupOption;
        }

        return $groupOptions;
    }
}
