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
/**
 * SF doc: https://symfony.com/doc/current/bundles/extension.html.
 */

namespace Gally\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\HttpKernel\DependencyInjection\Extension as BaseExtension;
use Symfony\Component\Yaml\Parser as YamlParser;
use Symfony\Component\Yaml\Yaml;

/**
 * @codeCoverageIgnore
 */
abstract class Extension extends BaseExtension implements PrependExtensionInterface
{
    protected function loadGallyConfigByFiles(ContainerBuilder $container, array $configFiles, $rootNodeConfig = Configuration::ROOT_NODE_CONFIG): void
    {
        $yamlParser ??= new YamlParser(); // @phpstan-ignore-line

        foreach ($configFiles as $configFile) {
            $container->prependExtensionConfig(
                $rootNodeConfig,
                $yamlParser->parseFile($configFile, Yaml::PARSE_CONSTANT)[$rootNodeConfig] ?? []
            );
        }
    }

    protected function loadGallyStandardConfigFile(ContainerBuilder $container, string $fileName, string $configNode): void
    {
        $this->loadGallyConfigFile($container, __DIR__ . '/../Configuration/Resources/config/' . $fileName, $configNode);
    }

    protected function loadGallyConfigFile(ContainerBuilder $container, string $fileName, string $configNode): void
    {
        $yamlParser ??= new YamlParser(); // @phpstan-ignore-line
        $config = $yamlParser->parseFile($fileName, Yaml::PARSE_CONSTANT);
        $container->prependExtensionConfig($configNode, $config[$configNode]);
    }

    protected function getPaths(string $pattern, ?string $relativeTo = null): array
    {
        $relativeTo = $relativeTo ? realpath($relativeTo) . '/' : '';

        return array_map(
            fn ($path) => str_replace($relativeTo, '', realpath($path)),
            glob($pattern)
        );
    }
}
