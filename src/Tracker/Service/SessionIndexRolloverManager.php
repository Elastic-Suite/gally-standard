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
use Gally\Index\Service\IndexOperation;
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
 * - Rollover: if the current (newest) tracking_session index is older than
 *   IndexSettings::getIsmRolloverAfter() (same configured value as tracking_event, never
 *   duplicated), a new one is created and added to the alias, and the Transform is repointed at
 *   it.
 * - Health: the transform can be auto-disabled by OpenSearch after a failure (e.g. a transient
 *   JVM circuit breaker) while tracking_session's index is still well within its rollover window.
 *   Index age alone would never notice this and the transform could stay dead for up to the whole
 *   rollover period, so it is checked independently of the index's age.
 * - Retention: any tracking_session index (other than the current one) older than
 *   IndexSettings::getIsmDeleteAfter() is deleted, mirroring tracking_event's own ISM "delete"
 *   transition. No-op if delete_after isn't configured, same as tracking_event's data stream then
 *   keeping every backing index forever.
 */
class SessionIndexRolloverManager
{
    public function __construct(
        private IndexSettingsInterface $indexSettings,
        private IndexOperation $indexOperation,
        private MetadataRepository $metadataRepository,
        private SessionTransformProvisioner $transformProvisioner,
        private LoggerInterface $logger,
    ) {
    }

    public function ensureUpToDate(LocalizedCatalog $localizedCatalog): void
    {
        $eventMetadata = $this->metadataRepository->findByEntity('tracking_event');
        $rolloverAfterDays = $this->indexSettings->getIsmRolloverAfter($localizedCatalog, $eventMetadata);

        if (null === $rolloverAfterDays) {
            // No rollover policy configured for tracking_event on this catalog: nothing to mirror.
            return;
        }

        $targetAlias = $this->indexSettings->getIndexAliasFromIdentifier('tracking_session', $localizedCatalog);
        $currentIndex = $this->indexOperation->findIndicesByAlias($targetAlias)[0] ?? null;
        $indexIsFresh = null !== $currentIndex && !$this->isOlderThanDays($currentIndex, $rolloverAfterDays);
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

    private function isOlderThanDays(Index $index, int $days): bool
    {
        $creationDateMs = (int) ($index->getSettings()['index']['creation_date'] ?? 0);

        if (0 === $creationDateMs) {
            // Unknown age: be safe and treat it as due for a refresh.
            return true;
        }

        $ageInDays = (time() - intdiv($creationDateMs, 1000)) / 86400;

        return $ageInDays >= $days;
    }
}
