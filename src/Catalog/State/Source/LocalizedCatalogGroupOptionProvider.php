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

namespace Gally\Catalog\State\Source;

use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use ApiPlatform\State\ProviderInterface;
use Gally\Catalog\Model\Source\LocalizedCatalogGroupOption;
use Gally\Catalog\Repository\CatalogRepository;

class LocalizedCatalogGroupOptionProvider implements ProviderInterface
{
    public function __construct(
        private CatalogRepository $catalogRepository
    ) {
    }

    /**
     * {@inheritdoc}
     *
     * @return T|PartialPaginatorInterface<T>|iterable<T>|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $groupOptions = [];
        foreach ($this->catalogRepository->findAll() as $catalog) {
            $groupOption['value'] = $groupOption['id'] = $catalog->getCode();
            $groupOption['label'] = $catalog->getName();
            $options = [];
            foreach ($catalog->getLocalizedCatalogs() as $localizedCatalog) {
                $option['value'] = "/localized_catalogs/{$localizedCatalog->getId()}";
                $option['label'] = $localizedCatalog->getName();
                $options[] = $option;
            }
            $groupOption['options'] = $options;
            $groupOptions[] = $groupOption;
        }

        return $groupOptions;
    }
}
