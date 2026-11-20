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
use Gally\Index\Entity\DataStream;
use Gally\Index\Entity\Index;
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
        self::deleteEntityElasticsearchDataStreams('tracking_event', self::$localizedCatalog->getId());
        static::getContainer()->get(IndexOperation::class)->deleteIndicesByAlias(self::$behavioralDataAlias);
        self::deleteEntityElasticsearchTransforms('tracking_session', self::$localizedCatalog->getId());
        self::deleteEntityElasticsearchIndices('tracking_session', self::$localizedCatalog->getId());
    }

    public function testEnsureUpToDateCreatesBehavioralDataIndexWhenNoneExistsYet(): void
    {
        static::getContainer()->get(BehavioralDataIndexRolloverManager::class)->ensureUpToDate(self::$localizedCatalog);

        $this->assertCount(1, static::getContainer()->get(IndexOperation::class)->findIndicesByAlias(self::$behavioralDataAlias));
        $this->assertContains(
            BehavioralDataTransformProvisioner::TRANSFORM_ID_PREFIX . self::$localizedCatalog->getCode(),
            array_map(fn ($transform) => $transform->getId(), static::getContainer()->get(TransformRepositoryInterface::class)->findAll())
        );
    }

    public function testEnsureUpToDateDoesNothingWhenIndexIsFreshAndTransformIsHealthy(): void
    {
        [$indexSettings, $indexOperation, $metadataRepository, $dataStreamRepository, $transformProvisioner, $configurationManager, $logger] = $this->getMocks();
        $this->stubMetadata($metadataRepository);
        $indexSettings->method('getIndexAliasFromIdentifier')->willReturn('behavioral_data_alias');
        $indexOperation->method('findIndicesByAlias')->willReturn([$this->buildIndexWithCreationDate('behavioral_data_current')]);
        $dataStreamRepository->method('findByMetadata')->willReturn($this->buildDataStream([$this->buildIndexWithCreationDate('.ds-tracking_event-000001')]));
        $transformProvisioner->method('isHealthy')->willReturn(true);

        $indexOperation->expects($this->never())->method('createEntityIndex');
        $transformProvisioner->expects($this->never())->method('createOrUpdate');

        (new BehavioralDataIndexRolloverManager($indexSettings, $indexOperation, $metadataRepository, $dataStreamRepository, $transformProvisioner, $configurationManager, $logger))
            ->ensureUpToDate((new LocalizedCatalog())->setCode('b2c_en'));
    }

    public function testEnsureUpToDateRecreatesTheTransformWhenItIsNotHealthy(): void
    {
        [$indexSettings, $indexOperation, $metadataRepository, $dataStreamRepository, $transformProvisioner, $configurationManager, $logger] = $this->getMocks();
        $localizedCatalog = (new LocalizedCatalog())->setCode('b2c_en');
        $this->stubMetadata($metadataRepository);
        $indexSettings->method('getIndexAliasFromIdentifier')->willReturn('behavioral_data_alias');
        $indexOperation->method('findIndicesByAlias')->willReturn([$this->buildIndexWithCreationDate('behavioral_data_current')]);
        $dataStreamRepository->method('findByMetadata')->willReturn($this->buildDataStream([$this->buildIndexWithCreationDate('.ds-tracking_event-000001')]));
        $transformProvisioner->method('isHealthy')->willReturn(false);

        $indexOperation->expects($this->never())->method('createEntityIndex');
        $transformProvisioner->expects($this->once())->method('createOrUpdate')->with($localizedCatalog);

        (new BehavioralDataIndexRolloverManager($indexSettings, $indexOperation, $metadataRepository, $dataStreamRepository, $transformProvisioner, $configurationManager, $logger))
            ->ensureUpToDate($localizedCatalog);
    }

    /**
     * @depends testEnsureUpToDateCreatesBehavioralDataIndexWhenNoneExistsYet
     */
    public function testEnsureUpToDateAfterEventRolloverCreatesANewIndexAndReadsTheNewBackingIndexOnly(): void
    {
        $client = static::getContainer()->get(Client::class);
        $indexOperation = static::getContainer()->get(IndexOperation::class);
        $dataStreamRepository = static::getContainer()->get(DataStreamRepositoryInterface::class);
        $eventMetadata = static::getContainer()->get(MetadataRepository::class)->findByEntity('tracking_event');
        $dataStreamName = $dataStreamRepository->findByMetadata($eventMetadata, self::$localizedCatalog)->getName();
        $rolloverManager = static::getContainer()->get(BehavioralDataIndexRolloverManager::class);

        $client->transport->resultOrFuture(
            $client->transport->performRequest('POST', "/$dataStreamName/_rollover"),
            []
        );

        // Index names are timestamped to the second: avoid a name collision with the previous one.
        sleep(1);
        $rolloverManager->ensureUpToDate(self::$localizedCatalog);

        $indices = $indexOperation->findIndicesByAlias(self::$behavioralDataAlias);
        $this->assertCount(2, $indices);
        $newestIndexName = array_reduce(
            $indices,
            fn (?string $newest, Index $index) => null === $newest || $index->getName() > $newest ? $index->getName() : $newest
        );
        $transformId = BehavioralDataTransformProvisioner::TRANSFORM_ID_PREFIX . self::$localizedCatalog->getCode();
        $transforms = array_filter(
            static::getContainer()->get(TransformRepositoryInterface::class)->findAll(),
            fn ($candidate) => $candidate->getId() === $transformId
        );
        $this->assertCount(1, $transforms);
        $transform = reset($transforms);
        $this->assertSame($newestIndexName, $transform->getTargetIndex());
        $writeIndex = $dataStreamRepository->findByMetadata($eventMetadata, self::$localizedCatalog)->getWriteIndex();
        $this->assertSame(urldecode($writeIndex->getName()), $transform->getSourceIndex());
    }

    public function testEnsureUpToDatePurgesOldIndicesAccordingToItsOwnRetention(): void
    {
        [$indexSettings, $indexOperation, $metadataRepository, $dataStreamRepository, $transformProvisioner, $configurationManager, $logger] = $this->getMocks();
        $this->stubMetadata($metadataRepository);
        $indexSettings->method('getIndexAliasFromIdentifier')->willReturn('behavioral_data_alias');
        $indexOperation->method('findIndicesByAlias')->willReturn([$this->buildIndexWithCreationDate('behavioral_data_current')]);
        $dataStreamRepository->method('findByMetadata')->willReturn($this->buildDataStream([$this->buildIndexWithCreationDate('.ds-tracking_event-000001')]));
        $transformProvisioner->method('isHealthy')->willReturn(true);
        $configurationManager->method('getScopedConfigValue')->willReturn(400);

        $indexOperation->expects($this->once())->method('deleteIndicesByAliasOlderThan')
            ->with('behavioral_data_alias', 400, ['behavioral_data_current']);

        (new BehavioralDataIndexRolloverManager($indexSettings, $indexOperation, $metadataRepository, $dataStreamRepository, $transformProvisioner, $configurationManager, $logger))
            ->ensureUpToDate((new LocalizedCatalog())->setCode('b2c_en'));
    }

    public function testEnsureUpToDateLogsExceptionInsteadOfPropagatingIt(): void
    {
        [$indexSettings, $indexOperation, $metadataRepository, $dataStreamRepository, $transformProvisioner, $configurationManager, $logger] = $this->getMocks();
        $this->stubMetadata($metadataRepository);
        $indexSettings->method('getIndexAliasFromIdentifier')->willReturn('behavioral_data_alias');
        $indexOperation->method('findIndicesByAlias')->willReturn([]);

        $exception = new \RuntimeException('OpenSearch is unreachable');
        $indexOperation->method('createEntityIndex')->willThrowException($exception);

        $transformProvisioner->expects($this->never())->method('createOrUpdate');
        $logger->expects($this->once())->method('error')->with($exception);

        (new BehavioralDataIndexRolloverManager($indexSettings, $indexOperation, $metadataRepository, $dataStreamRepository, $transformProvisioner, $configurationManager, $logger))
            ->ensureUpToDate((new LocalizedCatalog())->setCode('b2c_en'));
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
            $this->createMock(IndexSettingsInterface::class),
            $this->createMock(IndexOperation::class),
            $this->createMock(MetadataRepository::class),
            $this->createMock(DataStreamRepositoryInterface::class),
            $this->createMock(BehavioralDataTransformProvisioner::class),
            $this->createMock(ConfigurationManager::class),
            $this->createMock(LoggerInterface::class),
        ];
    }

    private function stubMetadata(MockObject $metadataRepository): void
    {
        $metadataRepository->method('findByEntity')->willReturnMap([
            ['tracking_event', true, (new Metadata())->setEntity('tracking_event')],
            ['behavioral_data', true, (new Metadata())->setEntity('behavioral_data')],
        ]);
    }

    private function buildIndexWithCreationDate(string $name): Index
    {
        $index = new Index($name);
        $index->setSettings(['index' => ['creation_date' => '1000000']]);

        return $index;
    }

    private function buildDataStream(array $indices): DataStream
    {
        $dataStream = new DataStream('tracking_event_alias');
        foreach ($indices as $index) {
            $dataStream->addIndex($index);
        }

        return $dataStream;
    }
}
