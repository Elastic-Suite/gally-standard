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
use Gally\Configuration\Entity\Configuration;
use Gally\Configuration\Repository\ConfigurationRepository;
use Gally\DependencyInjection\Extension;
use Gally\Exception\LogicException;
use Symfony\Component\Config\Definition\ArrayNode;
use Symfony\Component\Config\Definition\NodeInterface;
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
    public function getMultiScopedConfigurations(string $path, array $scopeCodeContext = []): array
    {
        $configurations = $this->getDefaultConfigurations($path);

        foreach ($this->configurationRepository->findByScope($path, $scopeCodeContext) as $configuration) {
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
    private function getDefaultConfigurations(string $path): array
    {
        $configurations = [];
        $pathAsArray = explode('.', $path);
        if ('' === $path) {
            return $configurations;
        }

        $rootNode = reset($pathAsArray);
        $node = $this->configTree[$rootNode];
        $value = $this->parameters->get($rootNode);
        foreach ($pathAsArray as $key) {
            if ($key == $node->getName()) {
                $value = $value[$key];
            } elseif ($node instanceof ArrayNode && \array_key_exists($key, $node->getChildren())) {
                if (!\array_key_exists($key, $value)) {
                    return $configurations;
                }
                $value = $value[$key];
                $node = $node->getChildren()[$key];
            } else {
                return $configurations;
            }
        }

        return $this->getFlattenConfiguration($node, $value, $path);
    }

    /**
     * Flatten default configurations to match database structure.
     *
     * @return Configuration[]
     */
    private function getFlattenConfiguration(NodeInterface $node, mixed $value, string $path): array
    {
        $configurations = [];

        if ($node instanceof ArrayNode) {
            foreach ($node->getChildren() as $name => $child) {
                $configurations = array_merge(
                    $configurations,
                    $this->getFlattenConfiguration($child, $value[$name] ?? null, implode('.', [$path, $name]))
                );
            }
        }

        if (empty($configurations)) {
            $configuration = new Configuration();
            $configuration->setId(0);
            $configuration->setPath($path);
            $configuration->setValue($value);
            $configurations[$path] = $configuration;
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
