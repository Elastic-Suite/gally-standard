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
/**
 * SF doc: https://symfony.com/doc/current/bundles/extension.html.
 */

namespace Gally\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension as BaseExtension;
use Symfony\Component\Yaml\Parser as YamlParser;
use Symfony\Component\Yaml\Yaml;

/**
 * @codeCoverageIgnore
 */
abstract class Extension extends BaseExtension implements PrependExtensionInterface
{
    public function __construct()
    {
        // Validate that bundle configuration implement gally interface.
        $this->getGallyConfiguration();
    }

    /**
     * Allows to load services config and set bundle parameters in container.
     *
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $bundleDirectory = \dirname((new \ReflectionClass(static::class))->getFileName()) . '/../';
        $loader = new YamlFileLoader($container, new FileLocator($bundleDirectory));

        $paths = array_merge(
            $this->getPaths($bundleDirectory . '*/Resources/config/services.yaml', $bundleDirectory),
            $this->getPaths($bundleDirectory . 'Resources/config/services.yaml', $bundleDirectory),
        );

        foreach ($paths as $path) {
            $loader->load($path);
        }

        if ('test' === $container->getParameter('kernel.environment')) {
            $paths = array_merge(
                $this->getPaths($bundleDirectory . '*/Resources/config/test/services.yaml', $bundleDirectory),
                $this->getPaths($bundleDirectory . 'Resources/config/test/services.yaml', $bundleDirectory),
            );
            foreach ($paths as $path) {
                $loader->load($path);
            }
        }

        $configuration = $this->getGallyConfiguration();
        if ($configuration) {
            $config = $this->processConfiguration($configuration, $configs);
            $container->setParameter($configuration->getRootNodeConfig(), [$configuration->getRootNodeConfig() => $config]);
        }

        // @Todo : Use this feature https://symfony.com/doc/current/bundles/extension.html ?
        //        $this->addAnnotatedClassesToCompile([
        //            // you can define the fully qualified class names...
        //            'App\\Controller\\DefaultController',
        //            // ... but glob patterns are also supported:
        //            '**Bundle\\Controller\\',
        //
        //            // ...
        //        ]);
    }

    public function getGallyConfiguration(): ?GallyConfigurationInterface
    {
        $class = static::class;

        if (str_contains($class, "\0")) {
            return null; // ignore anonymous classes
        }

        $class = substr_replace($class, '\Configuration', strrpos($class, '\\'));

        if (!class_exists($class)) {
            return null;
        }

        $interfaces = class_implements($class);
        if (!isset($interfaces[GallyConfigurationInterface::class])) {
            throw new LogicException(\sprintf('The gally extension configuration class "%s" must implement "%s".', $class, GallyConfigurationInterface::class));
        }

        return new $class();
    }

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
        $isTestMode = 'test' === $container->getParameter('kernel.environment');
        if ($isTestMode) {
            $this->loadGallyConfigFile($container, __DIR__ . '/../Configuration/Resources/config/test/' . $fileName, $configNode);
        }
        $this->loadGallyConfigFile($container, __DIR__ . '/../Configuration/Resources/config/' . $fileName, $configNode);
    }

    protected function loadGallyConfigFile(ContainerBuilder $container, string $fileName, string $configNode): void
    {
        if (!file_exists($fileName)) {
            return;
        }

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
