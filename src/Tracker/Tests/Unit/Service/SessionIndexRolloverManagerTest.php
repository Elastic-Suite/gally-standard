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
use Gally\Index\Dto\Bulk;
use Gally\Index\Repository\DataStream\DataStreamRepositoryInterface;
use Gally\Index\Repository\Transform\TransformRepositoryInterface;
use Gally\Index\Service\IndexOperation;
use Gally\Metadata\Entity\Metadata;
use Gally\Metadata\Repository\MetadataRepository;
use Gally\Test\AbstractTestCase;
use Gally\Tracker\Service\SessionIndexRolloverManager;
use Gally\Tracker\Service\SessionTransformProvisioner;
use OpenSearch\Client;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class SessionIndexRolloverManagerTest extends AbstractTestCase
{
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
        static::getContainer()->get(IndexOperation::class)->deleteIndicesByAlias(self::$sessionAlias);
    }

    /**
     * No tracking_session index yet for this catalog: one must be created.
     */
    public function testEnsureUpToDateCreatesSessionIndexWhenNoneExistsYet(): void
    {
        static::getContainer()->get(SessionIndexRolloverManager::class)->ensureUpToDate(self::$localizedCatalog);

        $sessionIndices = static::getContainer()->get(IndexOperation::class)->findIndicesByAlias(self::$sessionAlias);
        $this->assertCount(1, $sessionIndices);
        $this->assertTrue($this->waitUntilHealthy());
    }

    /**
     * Index already fresh and transform already healthy: nothing should change.
     *
     * @depends testEnsureUpToDateCreatesSessionIndexWhenNoneExistsYet
     */
    public function testEnsureUpToDateDoesNothingWhenIndexIsFreshAndTransformIsHealthy(): void
    {
        $indexOperation = static::getContainer()->get(IndexOperation::class);
        $before = $indexOperation->findIndicesByAlias(self::$sessionAlias);

        static::getContainer()->get(SessionIndexRolloverManager::class)->ensureUpToDate(self::$localizedCatalog);

        $after = $indexOperation->findIndicesByAlias(self::$sessionAlias);
        $this->assertCount(1, $after);
        $this->assertSame($before[0]->getName(), $after[0]->getName());
    }

    /**
     * tracking_event rolled over: the recreated transform must only aggregate sessions of the new backing index,
     * not re-aggregate the sessions whose events live in the old one (they already belong to the old session index).
     *
     * @depends testEnsureUpToDateCreatesSessionIndexWhenNoneExistsYet
     */
    public function testEnsureUpToDateAfterEventRolloverDoesNotReaggregateOldSessions(): void
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

        $newSessionUid = 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa';
        $bulkRequest = new Bulk\Request();
        $bulkRequest->addDocument($dataStreamRepository->findByName('tracking_event', self::$localizedCatalog), 'session_after_rollover_event', [
            '@timestamp' => (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'),
            'id' => 'session_after_rollover_event',
            'event_type' => 'search',
            'metadata_code' => 'product',
            'localized_catalog_code' => 'b2c_en',
            'session_uid' => $newSessionUid,
            'session_vid' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb',
            'group_id' => '0',
            'search_query' => ['query_text' => 'shoes', 'is_spellchecked' => false],
        ]);
        $dataStreamRepository->bulk($bulkRequest);
        $client->indices()->refresh(['index' => $dataStreamName]);

        // Session index names are timestamped to the second: avoid a name collision with the previous one.
        sleep(1);
        static::getContainer()->get(SessionIndexRolloverManager::class)->ensureUpToDate(self::$localizedCatalog);

        $sessionIndices = $indexOperation->findIndicesByAlias(self::$sessionAlias);
        $this->assertCount(2, $sessionIndices);
        $newestSessionIndexName = array_reduce(
            $sessionIndices,
            fn (?string $newest, $index) => null === $newest || $index->getName() > $newest ? $index->getName() : $newest
        );
        $transformId = SessionTransformProvisioner::TRANSFORM_ID_PREFIX . self::$localizedCatalog->getCode();
        $transforms = array_filter(
            static::getContainer()->get(TransformRepositoryInterface::class)->findAll(),
            fn ($candidate) => $candidate->getId() === $transformId
        );
        $this->assertCount(1, $transforms, "Transform $transformId not found.");
        $transform = reset($transforms);
        $this->assertSame($newestSessionIndexName, $transform->getTargetIndex());

        $response = $client->transport->resultOrFuture(
            $client->transport->performRequest('POST', '/_plugins/_transform/_preview', [], ['transform' => $transform->toArray()]),
            []
        );
        $previewedSessionUids = array_column($response['documents'], 'session_uid');
        $oldSessionUids = array_unique(array_column(
            json_decode((string) file_get_contents(__DIR__ . '/../../fixtures/tracking_event_for_session_documents.json'), true)[0]['documents'],
            'session_uid'
        ));
        $this->assertContains($newSessionUid, $previewedSessionUids);
        $this->assertSame([], array_intersect($oldSessionUids, $previewedSessionUids));
    }

    /**
     * delete_after configured: old generations behind the alias must be purged, current one kept.
     */
    public function testEnsureUpToDatePurgesOldGenerationsWhenDeleteAfterIsConfigured(): void
    {
        [$indexSettings, $indexOperation, $metadataRepository, $dataStreamRepository, $transformProvisioner, $logger] = $this->getMocks();
        $localizedCatalog = $this->getMockLocalizedCatalog();

        $this->stubMetadata($metadataRepository);
        $indexSettings->method('getIndexAliasFromIdentifier')->willReturn('tracking_session_alias');

        $sessionIndex = $this->buildIndexWithCreationDate('tracking_session_current');
        $indexOperation->method('findIndicesByAlias')->willReturn([$sessionIndex]);

        $backingIndex = $this->buildIndexWithCreationDate('.ds-tracking_event-000001');
        $dataStreamRepository->method('findByMetadata')->willReturn($this->buildDataStream([$backingIndex]));

        $transformProvisioner->method('isHealthy')->willReturn(true);
        $indexSettings->method('getIsmDeleteAfter')->willReturn(365);

        $indexOperation->expects($this->once())->method('deleteIndicesByAliasOlderThan')
            ->with('tracking_session_alias', 365, ['tracking_session_current']);

        $manager = new SessionIndexRolloverManager($indexSettings, $indexOperation, $metadataRepository, $dataStreamRepository, $transformProvisioner, $logger);
        $manager->ensureUpToDate($localizedCatalog);
    }

    /**
     * A collaborator throws while refreshing: the exception must be logged, not propagated.
     */
    public function testEnsureUpToDateLogsExceptionInsteadOfPropagatingIt(): void
    {
        [$indexSettings, $indexOperation, $metadataRepository, $dataStreamRepository, $transformProvisioner, $logger] = $this->getMocks();
        $localizedCatalog = $this->getMockLocalizedCatalog();

        $this->stubMetadata($metadataRepository);
        $indexSettings->method('getIndexAliasFromIdentifier')->willReturn('tracking_session_alias');
        $indexOperation->method('findIndicesByAlias')->willReturn([]);

        $exception = new \RuntimeException('OpenSearch is unreachable');
        $indexOperation->method('createEntityIndex')->willThrowException($exception);

        $transformProvisioner->expects($this->never())->method('createOrUpdate');
        $logger->expects($this->once())->method('error')->with($exception);

        $indexSettings->method('getIsmDeleteAfter')->willReturn(null);

        $manager = new SessionIndexRolloverManager($indexSettings, $indexOperation, $metadataRepository, $dataStreamRepository, $transformProvisioner, $logger);
        $manager->ensureUpToDate($localizedCatalog);
    }

    private function waitUntilHealthy(?LocalizedCatalog $localizedCatalog = null, int $maxAttempts = 180): bool
    {
        $localizedCatalog ??= self::$localizedCatalog;
        $provisioner = static::getContainer()->get(SessionTransformProvisioner::class);
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
     *     4: SessionTransformProvisioner&MockObject,
     *     5: LoggerInterface&MockObject,
     * }
     */
    private function getMocks(): array
    {
        return [
            $this->getMockBuilder(IndexSettingsInterface::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(IndexOperation::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(MetadataRepository::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(DataStreamRepositoryInterface::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(SessionTransformProvisioner::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(LoggerInterface::class)->disableOriginalConstructor()->getMock(),
        ];
    }

    private function stubMetadata(MockObject $metadataRepository): Metadata
    {
        $eventMetadata = (new Metadata())->setEntity('tracking_event');
        $sessionMetadata = (new Metadata())->setEntity('tracking_session');

        $metadataRepository->method('findByEntity')->willReturnMap([
            ['tracking_event', true, $eventMetadata],
            ['tracking_session', true, $sessionMetadata],
        ]);

        return $sessionMetadata;
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
