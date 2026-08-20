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

namespace Gally\Index\Tests\Unit;

use Gally\Index\Api\IndexSettingsInterface;
use Gally\Index\Entity\Index;
use Gally\Index\Repository\Index\IndexRepositoryInterface;
use Gally\Index\Service\IndexOperation;
use Gally\Index\Service\MetadataManager;
use Gally\Metadata\Repository\MetadataRepository;
use OpenSearch\Common\Exceptions\Missing404Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class IndexOperationTest extends KernelTestCase
{
    public function testFindIndicesByAliasReturnsIndicesNewestFirst(): void
    {
        $indexRepository = $this->getMockIndexRepository();
        $indexSettings = $this->getMockIndexSettings();
        $metadataRepository = $this->getMockMetadataRepository();

        $older = $this->buildIndexWithCreationDate('idx_older', 1_000_000);
        $newer = $this->buildIndexWithCreationDate('idx_newer', 2_000_000);

        $indexRepository->method('getMapping')->with('some_alias')->willReturn(['idx_older' => [], 'idx_newer' => []]);
        $indexRepository->method('findByName')->willReturnMap([
            ['idx_older', $older],
            ['idx_newer', $newer],
        ]);

        $indexOperation = $this->buildIndexOperation($indexRepository, $indexSettings, $metadataRepository);
        $result = $indexOperation->findIndicesByAlias('some_alias');

        $this->assertSame(['idx_newer', 'idx_older'], array_map(fn (Index $index) => $index->getName(), $result));
    }

    public function testFindIndicesByAliasReturnsEmptyArrayWhenAliasDoesNotExist(): void
    {
        $indexRepository = $this->getMockIndexRepository();
        $indexSettings = $this->getMockIndexSettings();
        $metadataRepository = $this->getMockMetadataRepository();

        $indexRepository->method('getMapping')->willThrowException($this->createMissing404Exception());

        $indexOperation = $this->buildIndexOperation($indexRepository, $indexSettings, $metadataRepository);

        $this->assertSame([], $indexOperation->findIndicesByAlias('missing_alias'));
    }

    public function testDeleteIndicesByAliasOlderThanDeletesOnlyOldUnskippedIndices(): void
    {
        $indexRepository = $this->getMockIndexRepository();
        $indexSettings = $this->getMockIndexSettings();
        $metadataRepository = $this->getMockMetadataRepository();

        $now = time();
        $oldEnough = $this->buildIndexWithCreationDate('idx_old', ($now - 200 * 86400) * 1000);
        $tooRecent = $this->buildIndexWithCreationDate('idx_recent', ($now - 5 * 86400) * 1000);
        $oldButSkipped = $this->buildIndexWithCreationDate('idx_skipped', ($now - 400 * 86400) * 1000);

        $indexRepository->method('getMapping')->with('session_alias')->willReturn([
            'idx_old' => [],
            'idx_recent' => [],
            'idx_skipped' => [],
        ]);
        $indexRepository->method('findByName')->willReturnMap([
            ['idx_old', $oldEnough],
            ['idx_recent', $tooRecent],
            ['idx_skipped', $oldButSkipped],
        ]);

        $indexRepository->expects($this->once())->method('updateAliases')->with([
            ['remove' => ['index' => 'idx_old', 'alias' => 'session_alias']],
        ]);
        $indexRepository->expects($this->once())->method('delete')->with('idx_old');

        $indexOperation = $this->buildIndexOperation($indexRepository, $indexSettings, $metadataRepository);
        $indexOperation->deleteIndicesByAliasOlderThan('session_alias', 30, ['idx_skipped']);
    }

    public function testDeleteIndicesByAliasOlderThanIgnoresIndicesWithUnknownCreationDate(): void
    {
        $indexRepository = $this->getMockIndexRepository();
        $indexSettings = $this->getMockIndexSettings();
        $metadataRepository = $this->getMockMetadataRepository();

        $unknownAge = new Index('idx_unknown');
        $unknownAge->setSettings([]);

        $indexRepository->method('getMapping')->willReturn(['idx_unknown' => []]);
        $indexRepository->method('findByName')->willReturn($unknownAge);

        $indexRepository->expects($this->never())->method('delete');

        $indexOperation = $this->buildIndexOperation($indexRepository, $indexSettings, $metadataRepository);
        $indexOperation->deleteIndicesByAliasOlderThan('session_alias', 30);
    }

    private function buildIndexWithCreationDate(string $name, int $creationDateMs): Index
    {
        $index = new Index($name);
        $index->setSettings(['index' => ['creation_date' => (string) $creationDateMs]]);

        return $index;
    }

    private function buildIndexOperation(
        MockObject $indexRepository,
        MockObject $indexSettings,
        MockObject $metadataRepository,
    ): IndexOperation {
        /** @var IndexRepositoryInterface $indexRepository */
        /** @var IndexSettingsInterface $indexSettings */
        /** @var MetadataRepository $metadataRepository */
        return new IndexOperation(
            $indexRepository,
            $indexSettings,
            $this->getMockMetadataManager(),
            $this->getMockEventDispatcher(),
            $metadataRepository
        );
    }

    private function createMissing404Exception(): Missing404Exception
    {
        return $this->getMockBuilder(Missing404Exception::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getMockIndexRepository(): MockObject
    {
        return $this->getMockBuilder(IndexRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getMockIndexSettings(): MockObject
    {
        return $this->getMockBuilder(IndexSettingsInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getMockMetadataManager(): MockObject
    {
        return $this->getMockBuilder(MetadataManager::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getMockEventDispatcher(): MockObject
    {
        return $this->getMockBuilder(EventDispatcherInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getMockMetadataRepository(): MockObject
    {
        return $this->getMockBuilder(MetadataRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
