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
use Gally\Configuration\Service\ConfigurationManager;
use Gally\Index\Api\IndexSettingsInterface;
use Gally\Index\Dto\Bulk;
use Gally\Index\Repository\DataStream\DataStreamRepositoryInterface;
use Gally\Index\Repository\Transform\TransformRepositoryInterface;
use Gally\Index\Service\IndexOperation;
use Gally\Metadata\Entity\Metadata;
use Gally\Metadata\Repository\MetadataRepository;
use Gally\Test\AbstractTestCase;
use Gally\Tracker\Service\BehavioralDataIndexRolloverManager;
use Gally\Tracker\Service\BehavioralDataTransformProvisioner;
use OpenSearch\Client;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class BehavioralDataIndexRolloverManagerTest extends AbstractTestCase
{
    private static LocalizedCatalog $localizedCatalog;
    private static string $behavioralDataAlias;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::loadFixture([
            __DIR__ . '/../../fixtures/catalogs.yaml',
            __DIR__ . '/../../fixtures/source_field.yaml',
            __DIR__ . '/../../fixtures/metadata.yaml',
        ]);

        self::$localizedCatalog = static::getContainer()->get(LocalizedCatalogRepository::class)->findByCodeOrId('b2c_en');
        self::$behavioralDataAlias = static::getContainer()->get(IndexSettingsInterface::class)
            ->getIndexAliasFromIdentifier('behavioral_data', self::$localizedCatalog);

        self::createEntityElasticsearchDataStream('tracking_event', self::$localizedCatalog->getId());
        self::loadElasticsearchDocumentFixtures([__DIR__ . '/../../fixtures/tracking_event_for_behavioral_data_documents.json']);
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        self::deleteEntityElasticsearchTransforms('behavioral_data', self::$localizedCatalog->getId());
        self::deleteEntityElasticsearchTransforms('tracking_session', self::$localizedCatalog->getId());
        self::deleteEntityElasticsearchDataStreams('tracking_event', self::$localizedCatalog->getId());
        static::getContainer()->get(IndexOperation::class)->deleteIndicesByAlias(self::$behavioralDataAlias);
        self::deleteEntityElasticsearchIndices('tracking_session', self::$localizedCatalog->getId());
    }

    /**
     * No behavioral_data index yet for this catalog: one must be created.
     */
    public function testEnsureUpToDateCreatesBehavioralDataIndexWhenNoneExistsYet(): void
    {
        static::getContainer()->get(BehavioralDataIndexRolloverManager::class)->ensureUpToDate(self::$localizedCatalog);

        $behavioralDataIndices = static::getContainer()->get(IndexOperation::class)->findIndicesByAlias(self::$behavioralDataAlias);
        $this->assertCount(1, $behavioralDataIndices);
        $this->assertTrue($this->waitUntilHealthy());
    }

    /**
     * Index already fresh and transform already healthy: nothing should change.
     *
     * @depends testEnsureUpToDateCreatesBehavioralDataIndexWhenNoneExistsYet
     */
    public function testEnsureUpToDateDoesNothingWhenIndexIsFreshAndTransformIsHealthy(): void
    {
        $indexOperation = static::getContainer()->get(IndexOperation::class);
        $before = $indexOperation->findIndicesByAlias(self::$behavioralDataAlias);

        static::getContainer()->get(BehavioralDataIndexRolloverManager::class)->ensureUpToDate(self::$localizedCatalog);

        $after = $indexOperation->findIndicesByAlias(self::$behavioralDataAlias);
        $this->assertCount(1, $after);
        $this->assertSame($before[0]->getName(), $after[0]->getName());
    }

    /**
     * tracking_event rolled over: the recreated transform must only aggregate events of the new backing index,
     * not re-aggregate the events that live in the old one (they already belong to the old behavioral_data index).
     *
     * @depends testEnsureUpToDateCreatesBehavioralDataIndexWhenNoneExistsYet
     */
    public function testEnsureUpToDateAfterEventRolloverDoesNotReaggregateOldEvents(): void
    {
        $client = static::getContainer()->get(Client::class);
        $indexOperation = static::getContainer()->get(IndexOperation::class);
        $dataStreamRepository = static::getContainer()->get(DataStreamRepositoryInterface::class);
        $eventMetadata = static::getContainer()->get(MetadataRepository::class)->findByEntity('tracking_event');
        $dataStreamName = $dataStreamRepository->findByMetadata($eventMetadata, self::$localizedCatalog)->getName();

        $client->transport->resultOrFuture(
            $client->transport->performRequest('POST', "/$dataStreamName/_rollover"),
            []
        );

        $newEntityCode = 'P_AFTER_ROLLOVER';
        $bulkRequest = new Bulk\Request();
        $bulkRequest->addDocument($dataStreamRepository->findByName('tracking_event', self::$localizedCatalog), 'behavioral_data_after_rollover_event', [
            '@timestamp' => (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'),
            'id' => 'behavioral_data_after_rollover_event',
            'event_type' => 'view',
            'metadata_code' => 'product',
            'localized_catalog_code' => 'b2c_en',
            'entity_code' => $newEntityCode,
        ]);
        $dataStreamRepository->bulk($bulkRequest);
        $client->indices()->refresh(['index' => $dataStreamName]);

        // Index names are timestamped to the second: avoid a name collision with the previous one.
        sleep(1);
        static::getContainer()->get(BehavioralDataIndexRolloverManager::class)->ensureUpToDate(self::$localizedCatalog);

        $behavioralDataIndices = $indexOperation->findIndicesByAlias(self::$behavioralDataAlias);
        $this->assertCount(2, $behavioralDataIndices);
        $newestBehavioralDataIndexName = array_reduce(
            $behavioralDataIndices,
            fn (?string $newest, $index) => null === $newest || $index->getName() > $newest ? $index->getName() : $newest
        );
        $transformId = BehavioralDataTransformProvisioner::TRANSFORM_ID_PREFIX . self::$localizedCatalog->getCode();
        $transforms = array_filter(
            static::getContainer()->get(TransformRepositoryInterface::class)->findAll(),
            fn ($candidate) => $candidate->getId() === $transformId
        );
        $this->assertCount(1, $transforms, "Transform $transformId not found.");
        $transform = reset($transforms);
        $this->assertSame($newestBehavioralDataIndexName, $transform->getTargetIndex());

        $response = $client->transport->resultOrFuture(
            $client->transport->performRequest('POST', '/_plugins/_transform/_preview', [], ['transform' => $transform->toArray()]),
            []
        );
        $previewedEntityCodes = array_column($response['documents'], 'entity_code');
        $oldEntityCodes = array_unique(array_column(
            json_decode((string) file_get_contents(__DIR__ . '/../../fixtures/tracking_event_for_behavioral_data_documents.json'), true)[0]['documents'],
            'entity_code'
        ));
        $this->assertContains($newEntityCode, $previewedEntityCodes);
        $this->assertSame([], array_intersect($oldEntityCodes, $previewedEntityCodes));
    }

    /**
     * Retention configured: old generations behind the alias must be purged, current one kept.
     */
    public function testEnsureUpToDatePurgesOldIndicesAccordingToItsOwnRetention(): void
    {
        [$indexSettings, $indexOperation, $metadataRepository, $dataStreamRepository, $transformProvisioner, $configurationManager, $logger] = $this->getMocks();
        $localizedCatalog = $this->getMockLocalizedCatalog();

        $this->stubMetadata($metadataRepository);
        $indexSettings->method('getIndexAliasFromIdentifier')->willReturn('behavioral_data_alias');

        $behavioralDataIndex = $this->buildIndexWithCreationDate('behavioral_data_current');
        $indexOperation->method('findIndicesByAlias')->willReturn([$behavioralDataIndex]);

        $backingIndex = $this->buildIndexWithCreationDate('.ds-tracking_event-000001');
        $dataStreamRepository->method('findByMetadata')->willReturn($this->buildDataStream([$backingIndex]));

        $transformProvisioner->method('isHealthy')->willReturn(true);
        $configurationManager->method('getScopedConfigValue')->willReturn(400);

        $indexOperation->expects($this->once())->method('deleteIndicesByAliasOlderThan')
            ->with('behavioral_data_alias', 400, ['behavioral_data_current']);

        $manager = new BehavioralDataIndexRolloverManager($indexSettings, $indexOperation, $metadataRepository, $dataStreamRepository, $transformProvisioner, $configurationManager, $logger, new ArrayAdapter());
        $manager->ensureUpToDate($localizedCatalog);
    }

    /**
     * An index created between retention and retention + rollover_after still holds data inside the retention window.
     */
    public function testEnsureUpToDateAddsTheEventRolloverPeriodToItsRetentionWhenPurging(): void
    {
        [$indexSettings, $indexOperation, $metadataRepository, $dataStreamRepository, $transformProvisioner, $configurationManager, $logger] = $this->getMocks();
        $localizedCatalog = $this->getMockLocalizedCatalog();

        $this->stubMetadata($metadataRepository);
        $indexSettings->method('getIndexAliasFromIdentifier')->willReturn('behavioral_data_alias');
        $indexSettings->method('getIsmRolloverAfter')->willReturn(30);

        $behavioralDataIndex = $this->buildIndexWithCreationDate('behavioral_data_current');
        $indexOperation->method('findIndicesByAlias')->willReturn([$behavioralDataIndex]);

        $backingIndex = $this->buildIndexWithCreationDate('.ds-tracking_event-000001');
        $dataStreamRepository->method('findByMetadata')->willReturn($this->buildDataStream([$backingIndex]));

        $transformProvisioner->method('isHealthy')->willReturn(true);
        $configurationManager->method('getScopedConfigValue')->willReturn(400);

        $indexOperation->expects($this->once())->method('deleteIndicesByAliasOlderThan')
            ->with('behavioral_data_alias', 430, ['behavioral_data_current']);

        $manager = new BehavioralDataIndexRolloverManager($indexSettings, $indexOperation, $metadataRepository, $dataStreamRepository, $transformProvisioner, $configurationManager, $logger, new ArrayAdapter());
        $manager->ensureUpToDate($localizedCatalog);
    }

    /**
     * A collaborator throws while refreshing: the exception must be logged, not propagated.
     */
    public function testEnsureUpToDateLogsExceptionInsteadOfPropagatingIt(): void
    {
        [$indexSettings, $indexOperation, $metadataRepository, $dataStreamRepository, $transformProvisioner, $configurationManager, $logger] = $this->getMocks();
        $localizedCatalog = $this->getMockLocalizedCatalog();

        $this->stubMetadata($metadataRepository);
        $indexSettings->method('getIndexAliasFromIdentifier')->willReturn('behavioral_data_alias');
        $indexOperation->method('findIndicesByAlias')->willReturn([]);

        $exception = new \RuntimeException('OpenSearch is unreachable');
        $indexOperation->method('createEntityIndex')->willThrowException($exception);

        $transformProvisioner->expects($this->never())->method('createOrUpdate');
        $logger->expects($this->once())->method('error')->with($exception);

        $manager = new BehavioralDataIndexRolloverManager($indexSettings, $indexOperation, $metadataRepository, $dataStreamRepository, $transformProvisioner, $configurationManager, $logger, new ArrayAdapter());
        $manager->ensureUpToDate($localizedCatalog);
    }

    private function waitUntilHealthy(?LocalizedCatalog $localizedCatalog = null, int $maxAttempts = 180): bool
    {
        $localizedCatalog ??= self::$localizedCatalog;
        $provisioner = static::getContainer()->get(BehavioralDataTransformProvisioner::class);
        for ($i = 0; $i < $maxAttempts; ++$i) {
            if ($provisioner->isHealthy($localizedCatalog)) {
                return true;
            }
            usleep(500_000);
        }

        return false;
    }

    /**
     * @return array{
     *     0: IndexSettingsInterface&MockObject,
     *     1: IndexOperation&MockObject,
     *     2: MetadataRepository&MockObject,
     *     3: DataStreamRepositoryInterface&MockObject,
     *     4: BehavioralDataTransformProvisioner&MockObject,
     *     5: ConfigurationManager&MockObject,
     *     6: LoggerInterface&MockObject,
     * }
     */
    private function getMocks(): array
    {
        return [
            $this->getMockBuilder(IndexSettingsInterface::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(IndexOperation::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(MetadataRepository::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(DataStreamRepositoryInterface::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(BehavioralDataTransformProvisioner::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(ConfigurationManager::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(LoggerInterface::class)->disableOriginalConstructor()->getMock(),
        ];
    }

    private function stubMetadata(MockObject $metadataRepository): Metadata
    {
        $eventMetadata = (new Metadata())->setEntity('tracking_event');
        $behavioralDataMetadata = (new Metadata())->setEntity('behavioral_data');

        $metadataRepository->method('findByEntity')->willReturnMap([
            ['tracking_event', true, $eventMetadata],
            ['behavioral_data', true, $behavioralDataMetadata],
        ]);

        return $behavioralDataMetadata;
    }

    private function buildIndexWithCreationDate(string $name): \Gally\Index\Entity\Index
    {
        $index = new \Gally\Index\Entity\Index($name);
        $index->setSettings(['index' => ['creation_date' => '1000000']]);

        return $index;
    }

    private function buildDataStream(array $indices): \Gally\Index\Entity\DataStream
    {
        $dataStream = new \Gally\Index\Entity\DataStream('tracking_event_alias');
        foreach ($indices as $index) {
            $dataStream->addIndex($index);
        }

        return $dataStream;
    }

    private function getMockLocalizedCatalog(): LocalizedCatalog&MockObject
    {
        return $this->getMockBuilder(LocalizedCatalog::class)->getMock();
    }
}
