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

namespace Gally\Tracker\Service;

use Gally\Catalog\Entity\LocalizedCatalog;
use Gally\Index\Api\IndexSettingsInterface;
use Gally\Index\Entity\Index;
use Gally\Index\Repository\DataStream\DataStreamRepositoryInterface;
use Gally\Index\Service\IndexOperation;
use Gally\Metadata\Entity\Metadata;
use Gally\Metadata\Repository\MetadataRepository;
use Psr\Log\LoggerInterface;

/**
 * Makes tracking_session follow the same rollover AND retention periodicity as tracking_event,
 * automatically, and recovers the Transform if it silently died in between.
 */
class SessionIndexRolloverManager
{
    public function __construct(
        private IndexSettingsInterface $indexSettings,
        private IndexOperation $indexOperation,
        private MetadataRepository $metadataRepository,
        private DataStreamRepositoryInterface $dataStreamRepository,
        private SessionTransformProvisioner $transformProvisioner,
        private LoggerInterface $logger,
    ) {
    }

    public function ensureUpToDate(LocalizedCatalog $localizedCatalog): void
    {
        $eventMetadata = $this->metadataRepository->findByEntity('tracking_event');
        $targetAlias = $this->indexSettings->getIndexAliasFromIdentifier('tracking_session', $localizedCatalog);
        $currentIndex = $this->indexOperation->findIndicesByAlias($targetAlias)[0] ?? null;
        $indexIsFresh = null !== $currentIndex && !$this->hasEventRolledOverSince($currentIndex, $eventMetadata, $localizedCatalog);
        $currentIndexName = $currentIndex?->getName();

        if (!$indexIsFresh || !$this->transformProvisioner->isHealthy($localizedCatalog)) {
            try {
                if (!$indexIsFresh) {
                    $sessionMetadata = $this->metadataRepository->findByEntity('tracking_session');
                    $newIndex = $this->indexOperation->createEntityIndex($sessionMetadata, $localizedCatalog);
                    $this->indexOperation->installIndexByName($newIndex->getName());
                    $currentIndexName = $newIndex->getName();
                }
                $this->transformProvisioner->createOrUpdate($localizedCatalog);
            } catch (\Exception $exception) {
                $this->logger->error($exception);
            }
        }

        $deleteAfterDays = $this->indexSettings->getIsmDeleteAfter($localizedCatalog, $eventMetadata);
        if (null !== $deleteAfterDays && null !== $currentIndexName) {
            $this->indexOperation->deleteIndicesByAliasOlderThan($targetAlias, $deleteAfterDays, [$currentIndexName]);
        }
    }

    /**
     * Whether tracking_event has rolled over since $sessionIndex was created (the real signal that tracking_session is now behind).
     */
    private function hasEventRolledOverSince(Index $sessionIndex, Metadata $eventMetadata, LocalizedCatalog $localizedCatalog): bool
    {
        $backingIndices = $this->dataStreamRepository->findByMetadata($eventMetadata, $localizedCatalog)?->getIndices() ?? [];
        $currentBackingIndex = $backingIndices[array_key_last($backingIndices)] ?? null;

        if (null === $currentBackingIndex) {
            // Unknown: be safe and treat it as due for a refresh.
            return true;
        }

        return $this->creationDate($currentBackingIndex) > $this->creationDate($sessionIndex);
    }

    private function creationDate(Index $index): int
    {
        return (int) ($index->getSettings()['index']['creation_date'] ?? 0);
    }
}
