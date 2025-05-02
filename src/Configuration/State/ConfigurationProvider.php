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
use Gally\Cache\Service\CacheManagerInterface;
use Gally\Catalog\Entity\LocalizedCatalog;
use Gally\Configuration\Entity\Configuration;

class ConfigurationProvider implements ProviderInterface
{
    public const LANGUAGE_DEFAULT = 'default';

    public function __construct(
        private CacheManagerInterface $cache,
        private array $defaultConfiguration,
    ) {
    }

    public function get(
        string $path,
        string $language = self::LANGUAGE_DEFAULT,
        string $requestType = null,
        ?LocalizedCatalog $localizedCatalog = null
    ): mixed {
        $defaultConfiguration = $this->getDefaultConfig($path);
        return new Configuration('toto', $path, $defaultConfiguration);
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return [
            ['id' => 'base_url/media', 'value' => $this->get('gally.base_url.media')]
        ];
    }

    private function getDefaultConfig(string $path): mixed
    {
        $pathAsArray = explode('.', $path);
        if (empty($pathAsArray)) {
            return null;
        }

        $config = $this->defaultConfiguration;
        foreach ($pathAsArray as $key) {
            if (array_key_exists($key, $config)) {
                $config = $config[$key];
            } else {
                return null;
            }
        }

        return $config;
    }
}
