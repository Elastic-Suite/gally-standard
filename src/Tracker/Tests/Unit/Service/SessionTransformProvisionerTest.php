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

namespace Gally\Tracker\Tests\Unit\Service;

use Gally\Catalog\Entity\LocalizedCatalog;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Index\Api\IndexSettingsInterface;
use Gally\Index\Service\IndexOperation;
use Gally\Test\AbstractTestCase;
use Gally\Tracker\Service\SessionTransformProvisioner;

class SessionTransformProvisionerTest extends AbstractTestCase
{
    private static SessionTransformProvisioner $provisioner;

    private static IndexOperation $indexOperation;

    private static LocalizedCatalog $localizedCatalog;

    private static string $sessionAlias;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::loadFixture([
            __DIR__ . '/../../fixtures/catalogs.yaml',
            __DIR__ . '/../../fixtures/source_field.yaml',
            __DIR__ . '/../../fixtures/metadata.yaml',
        ]);

        self::$provisioner = static::getContainer()->get(SessionTransformProvisioner::class);
        self::$indexOperation = static::getContainer()->get(IndexOperation::class);
        self::$localizedCatalog = static::getContainer()->get(LocalizedCatalogRepository::class)->findByCodeOrId('b2c_en');
        self::$sessionAlias = static::getContainer()->get(IndexSettingsInterface::class)
            ->getIndexAliasFromIdentifier('tracking_session', self::$localizedCatalog);

        self::createEntityElasticsearchDataStream('tracking_event', self::$localizedCatalog->getId());
        self::loadElasticsearchDocumentFixtures([__DIR__ . '/../../fixtures/tracking_event_for_session_documents.json']);
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        self::deleteEntityElasticsearchTransforms('tracking_session', self::$localizedCatalog->getId());
        self::deleteEntityElasticsearchDataStreams('tracking_event', self::$localizedCatalog->getId());
        self::deleteEntityElasticsearchIndices('tracking_session', self::$localizedCatalog->getId());
    }

    /**
     * The basic "creates a healthy transform over a real target index" case is covered by
     * TrackingEventHandlerTest::testHandleTrackingEventProvisionsARealTrackingSession, which
     * exercises it through the real production entry point instead of calling this service
     * directly.
     */
    public function testCreateOrUpdateReusesTheExistingIndexInsteadOfCreatingANewOne(): void
    {
        self::$provisioner->createOrUpdate(self::$localizedCatalog);
        $before = self::$indexOperation->findIndicesByAlias(self::$sessionAlias);
        $this->assertCount(1, $before);

        self::$provisioner->createOrUpdate(self::$localizedCatalog);

        $after = self::$indexOperation->findIndicesByAlias(self::$sessionAlias);
        $this->assertCount(1, $after);
        $this->assertSame($before[0]->getName(), $after[0]->getName());
    }

    /**
     * @depends testCreateOrUpdateReusesTheExistingIndexInsteadOfCreatingANewOne
     */
    public function testRemoveStopsAndDeletesTheTransform(): void
    {
        self::$provisioner->remove(self::$localizedCatalog);

        $this->assertFalse(self::$provisioner->isHealthy(self::$localizedCatalog));
    }
}
