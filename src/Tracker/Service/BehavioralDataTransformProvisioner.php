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
use Gally\Index\Entity\Transform;
use Gally\Index\Repository\DataStream\DataStreamRepositoryInterface;
use Gally\Index\Repository\Transform\TransformRepositoryInterface;
use Gally\Index\Service\IndexOperation;
use Gally\Index\Service\Transform\AbstractTransformProvisioner;
use Gally\Metadata\Repository\MetadataRepository;

/**
 * Builds and provisions the behavioral_data OpenSearch Transform for a localized catalog.
 * Self-sufficient: creates the behavioral_data index too if it doesn't exist yet.
 */
class BehavioralDataTransformProvisioner extends AbstractTransformProvisioner
{
    public const TRANSFORM_ID_PREFIX = 'behavioral_data_';
    private const DEFAULT_SCHEDULE_PERIOD = 5;

    public function __construct(
        TransformRepositoryInterface $transformRepository,
        private readonly IndexSettingsInterface $indexSettings,
        private readonly IndexOperation $indexOperation,
        private readonly MetadataRepository $metadataRepository,
        private readonly DataStreamRepositoryInterface $dataStreamRepository,
        private readonly ConfigurationManager $configurationManager,
    ) {
        parent::__construct($transformRepository);
    }

    public function getType(): string
    {
        return 'behavioral_data';
    }

    public function getTransformId(LocalizedCatalog $localizedCatalog): string
    {
        return self::TRANSFORM_ID_PREFIX . $localizedCatalog->getCode();
    }

    public function createOrUpdate(LocalizedCatalog $localizedCatalog): string
    {
        $transformId = $this->getTransformId($localizedCatalog);
        $targetAlias = $this->indexSettings->getIndexAliasFromIdentifier('behavioral_data', $localizedCatalog);
        $targetIndices = $this->indexOperation->findIndicesByAlias($targetAlias);
        $targetIndex = !empty($targetIndices) ? $targetIndices[0]->getName() : null;

        if (null === $targetIndex) {
            $behavioralDataMetadata = $this->metadataRepository->findByEntity('behavioral_data');
            $newIndex = $this->indexOperation->createEntityIndex($behavioralDataMetadata, $localizedCatalog);
            $this->indexOperation->installIndexByName($newIndex->getName());
            $targetIndex = $newIndex->getName();
        }

        $this->transformRepository->createOrUpdate($this->buildTransform(
            $transformId,
            $this->getSourceIndex($localizedCatalog),
            $targetIndex,
            $this->getSchedulePeriod($localizedCatalog),
        ));
        $this->transformRepository->start($transformId);

        return $transformId;
    }

    /**
     * Only the current write backing index: events living in an older backing index are already aggregated
     * in the previous behavioral_data index, and a transform reading the whole alias would duplicate them in the new one.
     */
    private function getSourceIndex(LocalizedCatalog $localizedCatalog): string
    {
        $eventMetadata = $this->metadataRepository->findByEntity('tracking_event');
        $writeIndex = $this->dataStreamRepository->findByMetadata($eventMetadata, $localizedCatalog)?->getWriteIndex();

        if (null === $writeIndex) {
            // No data stream yet: fall back to the alias, which will resolve to the first backing index once created.
            return $this->indexSettings->getIndexAliasFromIdentifier('tracking_event', $localizedCatalog);
        }

        return urldecode($writeIndex->getName());
    }

    private function getSchedulePeriod(LocalizedCatalog $localizedCatalog): int
    {
        $period = $this->configurationManager->getScopedConfigValue(
            'gally.tracking_behavioral_data_settings.transform_schedule_period',
            Configuration::SCOPE_LOCALIZED_CATALOG,
            $localizedCatalog->getCode()
        );

        return null !== $period ? (int) $period : self::DEFAULT_SCHEDULE_PERIOD;
    }

    private function buildTransform(string $transformId, string $sourceIndex, string $targetIndex, int $schedulePeriod): Transform
    {
        // Doc values are used instead of _source: views are the most numerous events, so this must stay cheap.
        $countOfEventType = fn (string $eventType) => [
            'scripted_metric' => [
                'init_script' => 'state.count = 0;',
                'map_script' => "if (doc['event_type'].size() > 0 && doc['event_type'].value == '{$eventType}') { state.count++; }",
                'combine_script' => 'return state.count;',
                'reduce_script' => 'long total = 0; for (s in states) { if (s != null) { total += s; } } return total;',
            ],
        ];
        // The order object is mapped as nested in tracking_event: a native sum aggregation cannot reach its fields.
        // Non-order events are skipped before reading _source.
        $sumOfOrderField = fn (string $field) => [
            'scripted_metric' => [
                'init_script' => 'state.sum = 0.0;',
                'map_script' => "if (doc['event_type'].size() == 0 || doc['event_type'].value != 'order') { return; } def order = params._source.order; if (order != null && order.{$field} != null) { state.sum += ((Number) order.{$field}).doubleValue(); }",
                'combine_script' => 'return state.sum;',
                'reduce_script' => 'double total = 0.0; for (s in states) { if (s != null) { total += s; } } return total;',
            ],
        ];

        return new Transform(
            id: $transformId,
            sourceIndex: $sourceIndex,
            targetIndex: $targetIndex,
            groups: [
                ['terms' => ['source_field' => 'entity_code', 'target_field' => 'entity_code']],
                ['date_histogram' => ['source_field' => '@timestamp', 'target_field' => 'date', 'fixed_interval' => '1d']],
            ],
            aggregations: [
                'views' => $countOfEventType('view'),
                'sales' => $countOfEventType('order'),
                'revenue' => $sumOfOrderField('row_total'),
            ],
            dataSelectionQuery: [
                'bool' => [
                    'filter' => [
                        ['term' => ['metadata_code' => 'product']],
                        ['terms' => ['event_type' => ['view', 'order']]],
                    ],
                ],
            ],
            continuous: true,
            description: "Aggregates {$sourceIndex} into {$targetIndex} (one document per entity_code and day)",
            schedule: [
                'interval' => [
                    'start_time' => time(),
                    'period' => $schedulePeriod,
                    'unit' => 'Minutes',
                ],
            ],
        );
    }
}
