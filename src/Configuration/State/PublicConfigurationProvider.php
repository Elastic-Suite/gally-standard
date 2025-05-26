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

namespace Gally\Configuration\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Gally\Configuration\Entity\Configuration;

class PublicConfigurationProvider implements ProviderInterface
{
    public function __construct(
        private ConfigurationProvider $configurationProvider,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Configuration|array|null
    {
        $context['only_public'] = true;
        $context['filters']['path'] = [];

        return $this->configurationProvider->provide($operation, $uriVariables, $context);
    }
}
