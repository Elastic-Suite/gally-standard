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

namespace Gally\Configuration\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;

class ConfigurationProvider implements ProviderInterface
{
    public function __construct(private array $baseUrl)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return [['id' => 'base_url/media', 'value' => $this->baseUrl['media']]];
    }
}
