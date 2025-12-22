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

namespace Gally\Configuration\State\Source;

use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use Gally\Catalog\Repository\CatalogRepository;
use Gally\Catalog\State\Source\LocalizedCatalogGroupOptionProvider as BaseLocalizedCatalogGroupOptionProvider;
use Gally\Configuration\Entity\Configuration;
use Symfony\Contracts\Translation\TranslatorInterface;

class LocalizedCatalogGroupOptionProvider extends BaseLocalizedCatalogGroupOptionProvider
{
    public function __construct(
        private CatalogRepository $catalogRepository,
        private IriConverterInterface $iriConverter,
        private TranslatorInterface $translator,
    ) {
        parent::__construct($this->catalogRepository, $this->iriConverter);
    }

    /**
     * @return iterable<mixed>|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        return array_merge(
            [[
                'value' => Configuration::SCOPE_GENERAL,
                'id' => Configuration::SCOPE_GENERAL,
                'label' => $this->translator->trans('gally_configuration.scope.default.label', [], 'gally_configuration'),
                'options' => [[
                    'value' => null,
                    'label' => $this->translator->trans('gally_configuration.scope.all_localized_catalogs.label', [], 'gally_configuration'),
                ]],
            ]],
            parent::provide($operation, $uriVariables, $context)
        );
    }
}
