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

namespace Gally\Doctrine\Service;

use Doctrine\DBAL\Configuration as DBALConfiguration;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\DefaultSchemaManagerFactory;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;

class EntityManagerFactory
{
    public function __construct(
        private ManagerRegistry $managerRegistry
    ) {
    }

    public function createIsolatedEntityManager(): EntityManager
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->managerRegistry->getManager();

        // Get connection parameters
        $connectionParams = $entityManager->getConnection()->getParams();

        // Create DBAL configuration with schema manager factory
        $dbalConfig = new DBALConfiguration();
        $dbalConfig->setSchemaManagerFactory(new DefaultSchemaManagerFactory());

        $isolatedConnection = DriverManager::getConnection($connectionParams, $dbalConfig);

        // Clone configuration to preserve all listeners and extensions
        $config = clone $entityManager->getConfiguration();

        // Create new isolated EntityManager
        return new EntityManager(
            $isolatedConnection,
            $config,
            $entityManager->getEventManager()
        );
    }
}
