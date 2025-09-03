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

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Configuration\Entity\Configuration;
use Symfony\Component\Intl\Exception\MissingResourceException;
use Symfony\Component\Intl\Locales;
use Symfony\Contracts\Translation\TranslatorInterface;
use Gally\Locale\State\Source\LocaleGroupOptionProvider as BaseLocaleGroupOptionProvider;

class LocaleGroupOptionProvider extends BaseLocaleGroupOptionProvider
{
    public function __construct(
        private LocalizedCatalogRepository $localizedCatalogRepository,
        private TranslatorInterface $translator,
    ) {
        parent::__construct($this->localizedCatalogRepository, $this->translator);
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?array
    {
        return array_merge(
            [[
                'value' => Configuration::SCOPE_GENERAL,
                'id' => Configuration::SCOPE_GENERAL,
                'label' => $this->translator->trans('gally_configuration.scope.default.label', [], 'gally_configuration'),
                'options' => [[
                    'value' => Configuration::SCOPE_GENERAL,
                    'label' => $this->translator->trans('gally_configuration.scope.all_locales.label', [], 'gally_configuration')
                ]],
            ]],
            parent::provide($operation, $uriVariables, $context)
        );
    }
}
