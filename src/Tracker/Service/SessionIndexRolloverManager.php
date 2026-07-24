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
use Gally\Index\Repository\Index\IndexRepositoryInterface;
use Gally\Index\Service\IndexOperation;
use Gally\Metadata\Repository\MetadataRepository;
use Psr\Log\LoggerInterface;

/**
 * Makes tracking_session follow the same rollover periodicity as tracking_event, automatically:
 * tracking_event's data stream rolls over based on IndexSettings::getIsmRolloverAfter(); this
 * reads that SAME configured value (never duplicated) and, if tracking_session's currently
 * installed index is older than it (or doesn't exist yet), (re)creates it and repoints the
 * OpenSearch Transform at the new physical index.
 *
 * tracking_session cannot itself be a data stream (see SessionTransformProvisioner: a Transform
 * destination must be upsertable, which data streams are not), so it can't get this rollover
 * "for free" the way tracking_event does -- this check drives it manually instead.
 */
class SessionIndexRolloverManager
{
    public function __construct(
        private IndexRepositoryInterface $indexRepository,
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
        $currentIndex = $this->indexRepository->findByName($targetAlias);

        if (null !== $currentIndex && !$this->isOlderThanDays($currentIndex, $rolloverAfterDays)) {
            return;
        }

        try {
            $sessionMetadata = $this->metadataRepository->findByEntity('tracking_session');
            $newIndex = $this->indexOperation->createEntityIndex($sessionMetadata, $localizedCatalog);
            $this->indexOperation->installIndexByName($newIndex->getName());
            $this->transformProvisioner->createOrUpdate($localizedCatalog);
        } catch (\Exception $exception) {
            $this->logger->error($exception);
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
