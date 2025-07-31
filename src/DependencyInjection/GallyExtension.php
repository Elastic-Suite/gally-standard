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

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @codeCoverageIgnore
 */
class GallyExtension extends Extension
{
    /**
     * Allows to set config for others bundles.
     *
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container): void
    {
        $this->loadGallyStandardConfigFile($container, 'doctrine_migrations.yaml', 'doctrine_migrations');
        $this->loadGallyStandardConfigFile($container, 'doctrine.yaml', 'doctrine');
        $this->loadGallyStandardConfigFile($container, 'api_platform.yaml', 'api_platform');
        $this->loadGallyStandardConfigFile($container, 'translation.yaml', 'framework');
        $this->loadGallyStandardConfigFile($container, 'nelmio_cors.yaml', 'nelmio_cors');

        $container->prependExtensionConfig(
            'api_platform',
            [
                'mapping' => [
                    'paths' => $this->getPaths(__DIR__ . '/../*/Entity/'),
                ],
            ]
        );

        $container->prependExtensionConfig(
            'framework',
            [
                'translator' => [
                    'paths' => $this->getPaths(__DIR__ . '/../*/Resources/translations'),
                ],
                'validation' => [
                    'enabled' => true,
                    'mapping' => [
                        'paths' => $this->getPaths(__DIR__ . '/../*/Resources/config/validator'),
                    ],
                ],
            ]
        );

        $this->loadGallyConfig($container);
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        parent::load($configs, $container);

        $configuration = $this->getGallyConfiguration();
        $config = $this->processConfiguration($configuration, $configs);
        $container->setParameter('gally.menu', $config['menu'] ?? []);
        $container->setParameter('gally.configuration', $config['configuration'] ?? []);
    }

    protected function loadGallyConfig(ContainerBuilder $container): void
    {
        $isTestMode = 'test' === $container->getParameter('kernel.environment');

        $configFiles = array_merge(
            $this->getPaths(__DIR__ . '/../*/Resources/config/gally.yaml'),
            $this->getPaths(__DIR__ . '/../*/Resources/config/gally_analysis.yaml'),
            $this->getPaths(__DIR__ . '/../*/Resources/config/gally_relevance.yaml'),
            $this->getPaths(__DIR__ . '/../*/Resources/config/gally_configuration.yaml'),
        );

        if ($isTestMode) {
            $configFiles = array_merge(
                $this->getPaths(__DIR__ . '/../*/Resources/config/test/gally*.yaml'),
                $configFiles,
            );
        } else {
            // Don't use getPath for menu conf, the order is important
            $configFiles = array_merge(
                $configFiles,
                [
                    __DIR__ . '/../Catalog/Resources/config/gally_menu.yaml',
                    __DIR__ . '/../User/Resources/config/gally_menu.yaml',
                    __DIR__ . '/../Menu/Resources/config/gally_menu.yaml',
                ]
            );
        }

        $this->loadGallyConfigByFiles($container, $configFiles);
    }
}
