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
    ) {
        parent::__construct($registry, Configuration::class);
    }

    /**
     * Get the list of all available scope types.
     *
     * @return string[]
     */
    public static function getAvailableScopeTypes(): array
    {
        return [
            Configuration::SCOPE_GENERAL,
            Configuration::SCOPE_LANGUAGE,
            Configuration::SCOPE_LOCALE,
            Configuration::SCOPE_REQUEST_TYPE,
            Configuration::SCOPE_LOCALIZED_CATALOG,
        ];
    }

    /**
     * Returns a list of configuration paths that cannot be accessed via the config manager.
     * These configurations should be injected directly into the services that require them.
     *
     * @return string[]
     */
    public function getBlacklistedPaths(): array
    {
        return [
            'gally.configuration',
            'gally.menu',
        ];
    }

    /**
     * Returns a list of configuration paths that can be accessed via the public api entrypoint.
     *
     * @return string[]
     */
    public function getPublicPaths(): array
    {
        return [
            'gally.base_url.media',
        ];
    }

    /**
     * Check if the given path is one of the blacklisted paths.
     */
    public function isPathValid(string $path, bool $onlyPublic = false): bool
    {
        foreach ($this->getBlacklistedPaths() as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return false;
            }
        }

        if ($onlyPublic) {
            return \in_array($path, $this->getPublicPaths(), true);
        }

        return true;
    }

    /**
     * Remove blacklisted paths from the list of given paths.
     */
    public function filterInvalidPaths(array $paths, bool $onlyPublic = false): array
    {
        $validPaths = [];
        foreach ($paths as $path) {
            if ($this->isPathValid($path, $onlyPublic)) {
                $validPaths[] = $path;
            }
        }

        return $validPaths;
    }

    /**
     * Define priority between scope type. It will be used to merge configuration in the good order.
     *
     * @return int[]
     */
    public function getScopePriority(): array
    {
        return [
            Configuration::SCOPE_LOCALIZED_CATALOG => 50,
            Configuration::SCOPE_REQUEST_TYPE => 40,
            Configuration::SCOPE_LOCALE => 30,
            Configuration::SCOPE_LANGUAGE => 20,
        ];
    }

    /**
     * Return configurations matching one of the provided scope (or default scope)
     * if the configuration path start with one of the given paths.
     *
     * @param array<string, string> $scopeCodeContext
     *
     * @return Configuration[]
     */
    public function findByScope(array|string $paths, array $scopeCodeContext = []): array
    {
        $queryBuilder = $this->createQueryBuilder('c');
        $conditions = ['c.scopeCode IS NULL'];
        $parameters = ['paths' => \is_string($paths) ? $paths . '%' : $paths];
        $priorityExpr = [];

        if (isset($scopeCodeContext[Configuration::SCOPE_LOCALE])
            && !isset($scopeCodeContext[Configuration::SCOPE_LANGUAGE])) {
            $scopeCodeContext[Configuration::SCOPE_LANGUAGE] = explode(
                '_',
                $scopeCodeContext[Configuration::SCOPE_LOCALE]
            )[0];
        }

        foreach ($this->getScopePriority() as $scopeType => $priority) {
            if (isset($scopeCodeContext[$scopeType])) {
                $conditions[] = $queryBuilder->expr()->andX("c.scopeType = :type$scopeType", "c.scopeCode = :$scopeType");
                $parameters["type$scopeType"] = $scopeType;
                $parameters["$scopeType"] = $scopeCodeContext[$scopeType];
                $priorityExpr[] = "WHEN c.scopeType = '$scopeType' THEN $priority";
            }
        }

        $queryBuilder->where(
            \is_string($paths)
            ? $queryBuilder->expr()->like('c.path', ':paths')
            : $queryBuilder->expr()->in('c.path', ':paths')
        )
            ->andWhere($queryBuilder->expr()->orX(...$conditions))
            ->addSelect(
                \count($priorityExpr)
                    ? '(CASE ' . implode(' ', $priorityExpr) . ' ELSE 10 END) AS HIDDEN scopePriority'
                    : '10 AS HIDDEN scopePriority'
            )
            ->orderBy('scopePriority', 'ASC')
            ->setParameters($parameters);

        return $queryBuilder->getQuery()->getResult();
    }
}
