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

namespace Gally\Configuration\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Gally\Bundle\Entity\ExtraBundle;
use Gally\Configuration\Entity\Configuration;
use Gally\DependencyInjection\Extension;
use Gally\Exception\LogicException;
use Symfony\Component\Config\Definition\ArrayNode;
use Symfony\Component\Config\Definition\NodeInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @method Configuration|null find($id, $lockMode = null, $lockVersion = null)
 * @method Configuration|null findOneBy(array $criteria, array $orderBy = null)
 * @method Configuration[]    findAll()
 * @method Configuration[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConfigurationRepository extends ServiceEntityRepository
{
    private $configTree;

    public function __construct(
        ManagerRegistry $registry,
        private KernelInterface $kernel,
        private ParameterBagInterface $parameters,
    ) {
        parent::__construct($registry, Configuration::class);
        $this->configTree = $this->buildConfigTree();
    }

    public function getScopePriority(): array
    {
        return [
            Configuration::SCOPE_LOCALIZED_CATALOG => 40,
            Configuration::SCOPE_REQUEST_TYPE => 30,
            Configuration::SCOPE_LOCALE => 20,
        ];
    }

    public function getScopedConfigValue(string $path, array $scopeCodeContext = []): mixed
    {
        $configs = $this->getScopedConfigurations($path, $scopeCodeContext);
        if (0 === \count($configs)) {
            return null;
        }
        if (\count($configs) > 1) {
            throw new LogicException('Multiple configurations have been found for the given path.');
        }

        return reset($configs)->getDecodedValue();
    }

    /**
     * @return Configuration[]
     */
    public function getScopedConfigurations(string $path, array $scopeCodeContext = []): array
    {
        $configurations = $this->getDefaultConfigurations($path);
        $queryBuilder = $this->createQueryBuilder('c');
        $conditions = ['c.scopeCode IS NULL'];
        $parameters = ['path' => $path];
        $priorityExpr = [];

        foreach ($this->getScopePriority() as $scopeCode => $priority) {
            if (isset($scopeCodeContext[$scopeCode])) {
                $conditions[] = $queryBuilder->expr()->andX("c.scopeType = :type$scopeCode", "c.scopeCode = :$scopeCode");
                $parameters["type$scopeCode"] = $scopeCode;
                $parameters["$scopeCode"] = $scopeCodeContext[$scopeCode];
                $priorityExpr[] = "WHEN c.scopeType = '$scopeCode' THEN $priority";
            }
        }

        $queryBuilder->where('c.path = :path')
            ->andWhere($queryBuilder->expr()->orX(...$conditions))
            ->addSelect(
                \count($priorityExpr)
                    ? '(CASE ' . implode(' ', $priorityExpr) . ' ELSE 10 END) AS HIDDEN scopePriority'
                    : '10 AS HIDDEN scopePriority'
            )
            ->orderBy('scopePriority', 'ASC')
            ->setParameters($parameters);

        foreach ($queryBuilder->getQuery()->getResult() as $configuration) {
            $configurations[$configuration->getPath()] = $configuration;
        }

        return $configurations;
    }

    /**
     * @return Configuration[]
     */
    private function getDefaultConfigurations(string $path): array
    {
        $configurations = [];
        $pathAsArray = explode('.', $path);
        if ([] === $pathAsArray) {
            return $configurations;
        }

        $rootNode = reset($pathAsArray);
        $node = $this->configTree[$rootNode];
        $value = $this->parameters->get($rootNode);
        foreach ($pathAsArray as $key) {
            if ($key == $node->getName()) {
                $value = $value[$key];
            } elseif ($node instanceof ArrayNode && \array_key_exists($key, $node->getChildren())) {
                $value = $value[$key];
                $node = $node->getChildren()[$key];
            } else {
                return $configurations;
            }
        }

        return $this->getFlattenConfiguration($node, $value, $path);
    }

    /**
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
     * Todo remove
     */
    private function flattenArray(array $array, string $prefix): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $fullKey = $prefix . '.' . $key;
            if (!\is_array($value) || array_is_list($value)) {
                $result[$fullKey] = \is_array($value) ? json_encode($value) : (string) $value;
            } else {
                $result += $this->flattenArray($value, $fullKey);
            }
        }

        return $result;
    }

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
