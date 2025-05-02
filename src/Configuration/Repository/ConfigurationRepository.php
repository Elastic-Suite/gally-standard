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
use Gally\Configuration\Entity\Configuration;
use Gally\Exception\LogicException;
use Symfony\Component\Config\Definition\ArrayNode;
use Symfony\Component\Config\Definition\NodeInterface;

/**
 * @method Configuration|null find($id, $lockMode = null, $lockVersion = null)
 * @method Configuration|null findOneBy(array $criteria, array $orderBy = null)
 * @method Configuration[]    findAll()
 * @method Configuration[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConfigurationRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private array $defaultConfiguration,
    ) {
        parent::__construct($registry, Configuration::class);
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

        return reset($configs)->decode()->getValue();
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
        $config = new \Gally\DependencyInjection\Configuration();
        $node = $config->getConfigTreeBuilder()->buildTree();
        $configurations = [];
        $pathAsArray = explode('.', $path);
        if ([] === $pathAsArray) {
            return $configurations;
        }

        $value = $this->defaultConfiguration;
        foreach ($pathAsArray as $key) {
            if ($key == $node->getName()) {
                $value = $value[$key];
            } elseif ($node instanceof ArrayNode && \array_key_exists($key, $node->getChildren())) {
                $value = $value[$key];
                $node = $node->getChildren()[$key];
            } else {
                // Todo what to do in this case
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
}
