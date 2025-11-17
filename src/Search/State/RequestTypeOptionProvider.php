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

namespace Gally\Search\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Gally\Search\Elasticsearch\Request\Container\Configuration\ContainerConfigurationProvider;

class RequestTypeOptionProvider implements ProviderInterface
{
    public function __construct(
        private ContainerConfigurationProvider $configurationProvider,
        private ProviderInterface $itemProvider,
    ) {
    }

    /**
     * @return object|array<int, array{value: string, label: string}>|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if (!$operation instanceof CollectionOperationInterface) {
            return $this->itemProvider->provide($operation, $uriVariables, $context);
        }

        $requestTypes = [];
        foreach ($this->configurationProvider->getAvailableRequestType('product') as $requestType) {
            $requestTypes[] = [
                'value' => $requestType,
                'label' => $requestType,
            ];
        }

        return $requestTypes;
    }
}
