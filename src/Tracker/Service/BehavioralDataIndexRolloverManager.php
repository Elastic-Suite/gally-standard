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
use Gally\Configuration\Entity\Configuration;
use Gally\Configuration\Service\ConfigurationManager;
use Gally\Index\Api\IndexSettingsInterface;
use Gally\Index\Entity\Index;
use Gally\Index\Repository\DataStream\DataStreamRepositoryInterface;
use Gally\Index\Service\IndexOperation;
use Gally\Metadata\Entity\Metadata;
use Gally\Metadata\Repository\MetadataRepository;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Makes behavioral_data follow the rollover periodicity of tracking_event, recovers the Transform if it silently died
 * in between, and purges old behavioral_data indices according to its own retention.
 */
class BehavioralDataIndexRolloverManager
{
    private const DEFAULT_RETENTION_IN_DAYS = 365;
    private const CHECK_INTERVAL_IN_SECONDS = 60;

    public function __construct(
        private readonly IndexSettingsInterface $indexSettings,
        private readonly IndexOperation $indexOperation,
        private readonly MetadataRepository $metadataRepository,
        private readonly DataStreamRepositoryInterface $dataStreamRepository,
        private readonly BehavioralDataTransformProvisioner $transformProvisioner,
        private readonly ConfigurationManager $configurationManager,
        private readonly LoggerInterface $logger,
        private readonly CacheInterface $cache,
    ) {
    }

    public function ensureUpToDate(LocalizedCatalog $localizedCatalog): void
    {
        $this->cache->get(
            \sprintf('behavioral_data_rollover_check_%d', $localizedCatalog->getId()),
            function (ItemInterface $item) use ($localizedCatalog): bool {
                $item->expiresAfter(self::CHECK_INTERVAL_IN_SECONDS);

                $eventMetadata = $this->metadataRepository->findByEntity('tracking_event');
                $targetAlias = $this->indexSettings->getIndexAliasFromIdentifier('behavioral_data', $localizedCatalog);
                $currentIndex = $this->indexOperation->findIndicesByAlias($targetAlias)[0] ?? null;
                $indexIsFresh = null !== $currentIndex && !$this->hasEventRolledOverSince($currentIndex, $eventMetadata, $localizedCatalog);
                $currentIndexName = $currentIndex?->getName();

                if (!$indexIsFresh || !$this->transformProvisioner->isHealthy($localizedCatalog)) {
                    try {
                        if (!$indexIsFresh) {
                            $behavioralDataMetadata = $this->metadataRepository->findByEntity('behavioral_data');
                            $newIndex = $this->indexOperation->createEntityIndex($behavioralDataMetadata, $localizedCatalog);
                            $this->indexOperation->installIndexByName($newIndex->getName());
                            $currentIndexName = $newIndex->getName();
                        }
                        $this->transformProvisioner->createOrUpdate($localizedCatalog);
                    } catch (\Exception $exception) {
                        $this->logger->error($exception);
                    }
                }

                if (null !== $currentIndexName) {
                    // An index is dated from its creation but keeps receiving data until the next rollover.
                    $this->indexOperation->deleteIndicesByAliasOlderThan(
                        $targetAlias,
                        $this->getRetentionInDays($localizedCatalog) + ($this->indexSettings->getIsmRolloverAfter($localizedCatalog, $eventMetadata) ?? 0),
                        [$currentIndexName]
                    );
                }

                return true;
            }
        );
    }

    private function getRetentionInDays(LocalizedCatalog $localizedCatalog): int
    {
        $retention = $this->configurationManager->getScopedConfigValue(
            'gally.tracking_behavioral_data_settings.retention',
            Configuration::SCOPE_LOCALIZED_CATALOG,
            $localizedCatalog->getCode()
        );

        return null !== $retention ? (int) $retention : self::DEFAULT_RETENTION_IN_DAYS;
    }

    private function hasEventRolledOverSince(Index $behavioralDataIndex, Metadata $eventMetadata, LocalizedCatalog $localizedCatalog): bool
    {
        $currentBackingIndex = $this->dataStreamRepository->findByMetadata($eventMetadata, $localizedCatalog)?->getWriteIndex();

        if (null === $currentBackingIndex) {
            // Unknown: be safe and treat it as due for a refresh.
            return true;
        }

        return $this->creationDate($currentBackingIndex) > $this->creationDate($behavioralDataIndex);
    }

    private function creationDate(Index $index): int
    {
        return (int) ($index->getSettings()['index']['creation_date'] ?? 0);
    }
}
