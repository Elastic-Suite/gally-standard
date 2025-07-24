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

namespace Gally\Configuration\Service;

use Gally\Bundle\Entity\ExtraBundle;
use Gally\Cache\Service\CacheManagerInterface;
use Gally\Configuration\Entity\Configuration;
use Gally\Configuration\Repository\ConfigurationRepository;
use Gally\DependencyInjection\Extension;
use Gally\Exception\LogicException;
use Symfony\Component\Config\Definition\ArrayNode;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Provides access to Gally configuration values, resolving them by scope and path.
 */
class ConfigurationManager
{
    private $configTree;

    public function __construct(
        private ConfigurationRepository $configurationRepository,
        private KernelInterface $kernel,
        private ParameterBagInterface $parameters,
        private CacheManagerInterface $cacheManager,
    ) {
        $this->configTree = $this->buildConfigTree();
    }

    /**
     * Returns the configuration value for the given path and context.
     * Throws an exception if the path is incomplete and would match multiple configurations.
     */
    public function getScopedConfigValue(string $path, ?string $scopeType = null, ?string $scopeValue = null): mixed
    {
        $configs = $this->getMultiScopedConfigurations($path, [$scopeType => $scopeValue]);
        if (0 === \count($configs)) {
            return null;
        }
        if (\count($configs) > 1) {
            throw new LogicException('Multiple configurations have been found for the given path.');
        }

        return reset($configs)->getDecodedValue();
    }

    /**
     * Get all configuration values starting with given path for the given context.
     */
    public function getScopedConfigValues(string $path, ?string $scopeType = null, ?string $scopeValue = null): array
    {
        $configurations = $this->getMultiScopedConfigurations($path, [$scopeType => $scopeValue]);
        $values = [];

        foreach ($configurations as $config) {
            $values[str_replace("$path.", '', $config->getPath())] = $config->getDecodedValue();
        }

        return $values;
    }

    /**
     * Get configurations starting with the given path.
     * For each path, only the most relevant configuration according to the given scope will be returned.
     * The scope code context is a key value association array where the key is the scopeType and the value is the scopeCode.
     *
     * @param array<string, string> $scopeCodeContext
     *
     * @return Configuration[]
     */
    public function getMultiScopedConfigurations(array|string $paths, array $scopeCodeContext = [], bool $onlyPublic = false): array
    {
        if (\is_string($paths) && !$this->configurationRepository->isPathValid($paths, $onlyPublic)) {
            return [];
        }
        if (\is_array($paths)) {
            if (empty($paths) && $onlyPublic) {
                $paths = $this->configurationRepository->getPublicPaths();
            } else {
                $paths = $this->configurationRepository->filterInvalidPaths($paths, $onlyPublic);
            }
        }

        $defaultConfigurations = [];

        foreach ($this->configTree as $key => $node) {
            if ($this->parameters->has($key)) {
                $values = $this->parameters->get($key);
                $defaultConfigurations += $this->cacheManager->get(
                    'gally_flatten_default_configurations_' . $key . '_' . $this->kernel->getEnvironment(),
                    function (&$tags, &$ttl) use ($key, $node, $values): array {
                        return $this->getFlattenDefaultConfigurations($key, $node, $values[$key]);
                    },
                    ['gally_flatten_configuration'],
                );
            }
        }

        if (!empty($paths)) {
            $configurations = \is_string($paths)
                ? (
                    \array_key_exists($paths, $defaultConfigurations)
                        ? [$paths => $defaultConfigurations[$paths]]
                        : array_filter(
                            $defaultConfigurations,
                            fn ($key) => str_starts_with($key, $paths), \ARRAY_FILTER_USE_KEY
                        )
                )
                : array_intersect_key($defaultConfigurations, array_flip($paths));
        } else {
            $configurations = $defaultConfigurations;
        }

        foreach ($this->configurationRepository->findByScope($paths, $scopeCodeContext) as $configuration) {
            if (!\array_key_exists($configuration->getPath(), $configurations)) {
                continue;
            }
            $configurations[$configuration->getPath()] = $configuration;
        }

        return $configurations;
    }

    /**
     * Get default configuration from yaml file according to tree define
     * in DependencyInjection\Configuration.php file of each bundle.
     *
     * @return Configuration[]
     */
    private function getFlattenDefaultConfigurations(string $path, ArrayNode $node, array $values): array
    {
        $configurations = [];

        foreach ($node->getChildren() as $key => $child) {
            if ($child instanceof ArrayNode && !empty($child->getChildren())) {
                $configurations += $this->getFlattenDefaultConfigurations($path . '.' . $key, $child, $values[$key] ?? []);
            } else {
                if ($this->configurationRepository->isPathValid($path . '.' . $key)) {
                    $configuration = new Configuration();
                    $configuration->setId(0);
                    $configuration->setPath($path . '.' . $key);
                    $configuration->setValue($values[$key] ?? null);
                    $configurations[$path . '.' . $key] = $configuration;
                }
            }
        }

        return $configurations;
    }

    /**
     * Build configuration tree structure base on tree defined
     * in DependencyInjection\Configuration.php file of each bundle.
     */
    private function buildConfigTree(): array
    {
        $configTree = [];
        foreach ($this->kernel->getBundles() as $bundle) {
            if (str_starts_with($bundle->getName(), ExtraBundle::GALLY_BUNDLE_PREFIX)) {
                $extension = $bundle->getContainerExtension();
                if ($extension instanceof Extension) {
                    $configuration = $extension->getGallyConfiguration();
                    if ($configuration) {
                        $configTree[$configuration->getRootNodeConfig()] = $configuration->getConfigTreeBuilder()->buildTree();
                    }
                }
            }
        }

        return $configTree;
    }
}
