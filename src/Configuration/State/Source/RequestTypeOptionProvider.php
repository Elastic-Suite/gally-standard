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

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Gally\Configuration\Entity\Configuration;
use Gally\Search\Elasticsearch\Request\Container\Configuration\ContainerConfigurationProvider;
use Gally\Search\State\RequestTypeOptionProvider as BaseRequestTypeOptionProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class RequestTypeOptionProvider extends BaseRequestTypeOptionProvider
{
    public function __construct(
        private ContainerConfigurationProvider $configurationProvider,
        private ProviderInterface $itemProvider,
        private TranslatorInterface $translator,
    ) {
        parent::__construct($this->configurationProvider, $this->itemProvider);
    }

    /**
     * @return object|array<int, array{value: string, label: string}>|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if (!$operation instanceof CollectionOperationInterface) {
            return parent::provide($operation, $uriVariables, $context);
        }

        return array_merge(
            [[
                'value' => Configuration::SCOPE_GENERAL,
                'label' => $this->translator->trans('gally_configuration.scope.all_request_types.label', [], 'gally_configuration'),
            ]],
            parent::provide($operation, $uriVariables, $context)
        );
    }
}
