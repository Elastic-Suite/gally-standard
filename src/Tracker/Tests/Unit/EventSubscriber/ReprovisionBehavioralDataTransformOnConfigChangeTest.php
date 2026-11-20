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

namespace Gally\Tracker\Tests\Unit\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Gally\Catalog\Entity\LocalizedCatalog;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Configuration\Entity\Configuration;
use Gally\Tracker\EventSubscriber\ReprovisionBehavioralDataTransformOnConfigChange;
use Gally\Tracker\Service\BehavioralDataTransformProvisioner;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ReprovisionBehavioralDataTransformOnConfigChangeTest extends TestCase
{
    public function testReprovisionsTheTransformOfEveryCatalog(): void
    {
        $localizedCatalog = new LocalizedCatalog();
        $provisioner = $this->createMock(BehavioralDataTransformProvisioner::class);
        $provisioner->expects($this->once())->method('createOrUpdate')->with($localizedCatalog);

        $this->flushConfigurationChange('gally.tracking_behavioral_data_settings.transform_schedule_period', $this->buildSubscriber($provisioner, [$localizedCatalog]));
    }

    public function testIgnoresUnrelatedConfigurationChanges(): void
    {
        $provisioner = $this->createMock(BehavioralDataTransformProvisioner::class);
        $provisioner->expects($this->never())->method('createOrUpdate');

        $this->flushConfigurationChange('gally.tracking_behavioral_data_settings.retention', $this->buildSubscriber($provisioner, [new LocalizedCatalog()]));
    }

    public function testLogsProvisioningFailureInsteadOfPropagatingIt(): void
    {
        $exception = new \RuntimeException('OpenSearch is unreachable');
        $provisioner = $this->createMock(BehavioralDataTransformProvisioner::class);
        $provisioner->method('createOrUpdate')->willThrowException($exception);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with($exception);

        $this->flushConfigurationChange('gally.tracking_behavioral_data_settings.transform_schedule_period', $this->buildSubscriber($provisioner, [new LocalizedCatalog()], $logger));
    }

    private function flushConfigurationChange(string $path, ReprovisionBehavioralDataTransformOnConfigChange $subscriber): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $configuration = new Configuration();
        $configuration->setPath($path);

        $subscriber->postUpdate(new PostUpdateEventArgs($configuration, $entityManager));
        $subscriber->postFlush(new PostFlushEventArgs($entityManager));
    }

    private function buildSubscriber(
        BehavioralDataTransformProvisioner $provisioner,
        array $localizedCatalogs,
        ?LoggerInterface $logger = null,
    ): ReprovisionBehavioralDataTransformOnConfigChange {
        $localizedCatalogRepository = $this->createMock(LocalizedCatalogRepository::class);
        $localizedCatalogRepository->method('findAll')->willReturn($localizedCatalogs);

        return new ReprovisionBehavioralDataTransformOnConfigChange(
            $localizedCatalogRepository,
            $provisioner,
            $logger ?? $this->createMock(LoggerInterface::class)
        );
    }
}
