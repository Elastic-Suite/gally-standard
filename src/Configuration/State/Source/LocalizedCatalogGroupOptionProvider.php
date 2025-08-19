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

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\State\ProviderInterface;
use Gally\Catalog\Repository\CatalogRepository;
use Gally\Configuration\Entity\Configuration;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Gally\Catalog\State\Source\LocalizedCatalogGroupOptionProvider as BaseLocalizedCatalogGroupOptionProvider;

class LocalizedCatalogGroupOptionProvider extends BaseLocalizedCatalogGroupOptionProvider
{
    public function __construct(
        private CatalogRepository $catalogRepository,
        private IriConverterInterface $iriConverter,
        private RequestStack $requestStack,
        private TranslatorInterface $translator,
    ) {
        parent::__construct($this->catalogRepository, $this->iriConverter, $this->requestStack);
    }

    /**
     * @return iterable<mixed>|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        return array_merge(
            [[
                //todo: si on ne veut pas afficher le group dans la dropdown en front on peut supprimer les lignes "value", "id", "label".
                'value' => Configuration::SCOPE_GENERAL,
                'id' => Configuration::SCOPE_GENERAL,
                'label' => $this->translator->trans('gally_configuration.scope.default.label', [], 'gally_configuration'),
                'options' => [[
                    'value' => Configuration::SCOPE_GENERAL,
                    'label' => $this->translator->trans('gally_configuration.scope.default.label', [], 'gally_configuration'), // todo: changer label par "Tous les catalogues localisés"
                ]],
            ]],
            parent::provide($operation, $uriVariables, $context)
        );
    }
}
