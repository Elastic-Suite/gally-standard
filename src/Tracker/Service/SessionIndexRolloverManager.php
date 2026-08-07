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
 *
 * tracking_session cannot itself be a data stream (see SessionTransformProvisioner: a Transform
 * destination must be upsertable, which data streams are not), so it mirrors a data stream's
 * behavior by hand: several aged index generations are kept alive behind one alias for reads
 * (IndexOperation::addIndexToAlias(), instead of the usual blue-green swap-and-delete), and only
 * removed once they age past delete_after -- exactly like a data stream's own ISM "delete"
 * transition. Keeping just the newest generation alive (a plain swap on rollover) would make
 * tracking_session's data disappear at rollover_after days while tracking_event's raw events
 * covering that same period stay queryable until delete_after: any query spanning that gap would
 * see events but no sessions for them.
 *
 * Three independent things are checked on every call:
 * - Rollover: tracking_event's own data stream is the source of truth for whether a rollover
 *   happened -- not an independently-computed "should have rolled over by now" time estimate,
 *   which could drift from reality (the ISM job runs periodically, not instantly, and
 *   rollover_after is read live so a config change would desync it from what tracking_event
 *   actually already did). If tracking_event's current backing index was created after
 *   tracking_session's current index, a new one is created and added to the alias, and the
 *   Transform is repointed at it.
 * - Health: the transform can be auto-disabled by OpenSearch after a failure (e.g. a transient
 *   JVM circuit breaker) while tracking_session's index is still well within its rollover window.
 *   Comparing backing indices alone would never notice this and the transform could stay dead
 *   indefinitely, so it is checked independently.
 * - Retention: any tracking_session index (other than the current one) older than
 *   IndexSettings::getIsmDeleteAfter() is deleted, mirroring tracking_event's own ISM "delete"
 *   transition (also purely age-based on that side, so no equivalent drift risk here). No-op if
 *   delete_after isn't configured, same as tracking_event's data stream then keeping every
 *   backing index forever.
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
                    $this->indexOperation->addIndexToAlias($newIndex->getName());
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
     * Whether tracking_event's data stream has actually rolled over to a backing index created
     * after $sessionIndex -- the direct signal that tracking_session is now behind, as opposed to
     * a time estimate that could disagree with what tracking_event's data stream really did.
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
