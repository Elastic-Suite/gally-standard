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

namespace Gally\Catalog\State\Source;

use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\State\ProviderInterface;
use Gally\Catalog\Repository\CatalogRepository;

class LocalizedCatalogGroupOptionProvider implements ProviderInterface
{
    public function __construct(
        private CatalogRepository $catalogRepository,
        private IriConverterInterface $iriConverter,
    ) {
    }

    /**
     * @return iterable<mixed>|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $keyToGet = $context['args']['keyToGetOnValue']
            ?? (isset($context['request']) ? $context['request']->get('keyToGetOnValue') : null);
        $groupOptions = [];
        foreach ($this->catalogRepository->findAll() as $catalog) {
            $groupOption['value'] = $groupOption['id'] = $catalog->getCode();
            $groupOption['label'] = $catalog->getName();
            $options = [];
            foreach ($catalog->getLocalizedCatalogs() as $localizedCatalog) {
                $getValueMethod = \sprintf('get%s', ucfirst($keyToGet ?? ''));
                if (null !== $keyToGet && method_exists($localizedCatalog, $getValueMethod)) {
                    $option['value'] = $localizedCatalog->{$getValueMethod}();
                } else {
                    $option['value'] = $this->iriConverter->getIriFromResource($localizedCatalog, UrlGeneratorInterface::ABS_PATH);
                }

                $option['label'] = $localizedCatalog->getName();
                $options[] = $option;
            }
            $groupOption['options'] = $options;
            $groupOptions[] = $groupOption;
        }

        return $groupOptions;
    }
}
