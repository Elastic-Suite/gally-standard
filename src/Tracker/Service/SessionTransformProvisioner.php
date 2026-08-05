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
use Gally\Index\Entity\Transform;
use Gally\Index\Repository\Index\IndexRepositoryInterface;
use Gally\Index\Repository\Transform\TransformRepositoryInterface;
use Gally\Index\Service\IndexOperation;
use Gally\Metadata\Repository\MetadataRepository;

/**
 * Builds and provisions the tracking_session-specific OpenSearch Transform definition for a
 * localized catalog. All the generic OpenSearch Transform plumbing (create/update/start/stop)
 * lives in TransformRepositoryInterface, which is entity-agnostic and reusable for any other
 * future Transform-based aggregation in Gally.
 *
 * Self-sufficient: if tracking_session has no index yet for this catalog, it gets created here
 * too. This matters for entry points other than the automated rollover check (which already
 * creates the index itself) -- e.g. running this by hand right after loading fixtures, since
 * fixtures bulk-load tracking_event documents directly and never go through
 * TrackingEventHandler, so the automated hook never fires for them.
 */
class SessionTransformProvisioner
{
    private const TRANSFORM_ID_PREFIX = 'tracking_session_';

    public function __construct(
        private TransformRepositoryInterface $transformRepository,
        private IndexRepositoryInterface $indexRepository,
        private IndexSettingsInterface $indexSettings,
        private IndexOperation $indexOperation,
        private MetadataRepository $metadataRepository,
    ) {
    }

    public function createOrUpdate(LocalizedCatalog $localizedCatalog): string
    {
        $sourceIndex = $this->indexSettings->getIndexAliasFromIdentifier('tracking_event', $localizedCatalog);
        $targetAlias = $this->indexSettings->getIndexAliasFromIdentifier('tracking_session', $localizedCatalog);
        $targetIndex = $this->indexRepository->findByName($targetAlias)?->getName();

        if (null === $targetIndex) {
            $sessionMetadata = $this->metadataRepository->findByEntity('tracking_session');
            $newIndex = $this->indexOperation->createEntityIndex($sessionMetadata, $localizedCatalog);
            $this->indexOperation->installIndexByName($newIndex->getName());
            $targetIndex = $newIndex->getName();
        }

        $transformId = self::TRANSFORM_ID_PREFIX . $localizedCatalog->getCode();

        $this->transformRepository->createOrUpdate($this->buildTransform($transformId, $sourceIndex, $targetIndex));
        $this->transformRepository->start($transformId);

        return $transformId;
    }

    /**
     * Whether the transform is actually running (or just started, about to run its first
     * checkpoint) -- as opposed to missing, stopped, or auto-disabled by OpenSearch after a
     * failure (e.g. a transient JVM circuit breaker). Index age alone (see
     * SessionIndexRolloverManager) cannot catch this: a transform can silently die while its
     * target index is still well within its rollover window.
     */
    public function isHealthy(LocalizedCatalog $localizedCatalog): bool
    {
        $transformId = self::TRANSFORM_ID_PREFIX . $localizedCatalog->getCode();

        try {
            $explain = $this->transformRepository->explain($transformId);
            $status = $explain[$transformId]['transform_metadata']['status'] ?? null;
        } catch (\Exception) {
            return false;
        }

        return \in_array($status, ['started', 'init'], true);
    }

    public function remove(LocalizedCatalog $localizedCatalog): void
    {
        $transformId = self::TRANSFORM_ID_PREFIX . $localizedCatalog->getCode();

        try {
            $this->transformRepository->stop($transformId);
        } catch (\Exception) {
            // Already stopped or never started.
        }

        try {
            $this->transformRepository->delete($transformId);
        } catch (\Exception) {
            // Nothing to delete.
        }
    }

    private function buildTransform(string $transformId, string $sourceIndex, string $targetIndex): Transform
    {
        $firstNonNullValue = fn (string $field) => [
            'scripted_metric' => [
                'init_script' => 'state.value = null;',
                'map_script' => "if (state.value == null) { state.value = params._source.{$field}; }",
                'combine_script' => 'return state.value;',
                'reduce_script' => 'for (v in states) { if (v != null) { return v; } } return null;',
            ],
        ];

        $groupedByMetadataCode = fn (string $eventType) => [
            'scripted_metric' => [
                'init_script' => 'state.byType = new HashMap();',
                'map_script' => "if (params._source.event_type == '{$eventType}') { String mc = params._source.metadata_code; String ec = params._source.entity_code; if (mc != null && ec != null) { if (!state.byType.containsKey(mc)) { state.byType.put(mc, new HashSet()); } state.byType.get(mc).add(ec); } }",
                'combine_script' => 'return state.byType;',
                'reduce_script' => "Map merged = new HashMap(); for (m in states) { if (m == null) continue; for (e in m.entrySet()) { if (!merged.containsKey(e.getKey())) { merged.put(e.getKey(), new HashSet()); } merged.get(e.getKey()).addAll(e.getValue()); } } List result = new ArrayList(); for (e in merged.entrySet()) { Map o = new HashMap(); o.put('metadata_code', e.getKey()); List items = new ArrayList(e.getValue()); o.put('items', items); o.put('count', items.size()); result.add(o); } return result;",
            ],
        ];

        $groups = [
            ['terms' => ['source_field' => 'session_uid', 'target_field' => 'session_uid']],
        ];

        $aggregations = [
            'session_vid' => $firstNonNullValue('session_vid'),
            'group_id' => $firstNonNullValue('group_id'),
            'localized_catalog_code' => $firstNonNullValue('localized_catalog_code'),
            'start_time' => [
                'scripted_metric' => [
                    'init_script' => 'state.value = null;',
                    'map_script' => "def v = params._source['@timestamp']; if (v != null && (state.value == null || v.compareTo(state.value) < 0)) { state.value = v; }",
                    'combine_script' => 'return state.value;',
                    'reduce_script' => 'def result = null; for (v in states) { if (v != null && (result == null || v.compareTo(result) < 0)) { result = v; } } return result;',
                ],
            ],
            'end_time' => [
                'scripted_metric' => [
                    'init_script' => 'state.value = null;',
                    'map_script' => "def v = params._source['@timestamp']; if (v != null && (state.value == null || v.compareTo(state.value) > 0)) { state.value = v; }",
                    'combine_script' => 'return state.value;',
                    'reduce_script' => 'def result = null; for (v in states) { if (v != null && (result == null || v.compareTo(result) > 0)) { result = v; } } return result;',
                ],
            ],
            '@timestamp' => [
                'scripted_metric' => [
                    'init_script' => 'state.value = null;',
                    'map_script' => "def v = params._source['@timestamp']; if (v != null && (state.value == null || v.compareTo(state.value) > 0)) { state.value = v; }",
                    'combine_script' => 'return state.value;',
                    'reduce_script' => 'def result = null; for (v in states) { if (v != null && (result == null || v.compareTo(result) > 0)) { result = v; } } return result;',
                ],
            ],
            'views' => $groupedByMetadataCode('view'),
            'cart' => $groupedByMetadataCode('add_to_cart'),
            'order' => $groupedByMetadataCode('order'),
            'searches' => [
                'scripted_metric' => [
                    'init_script' => 'state.data = new HashMap();',
                    'map_script' => "if (params._source.event_type == 'search') { def sq = params._source.search_query; String q = sq != null ? sq.query_text : null; if (q != null) { def pl = params._source.product_list; long rc = (pl != null && pl.item_count != null) ? (long) pl.item_count : 0L; Map cur = state.data.get(q); if (cur == null) { cur = new HashMap(); cur.put('count', 0L); cur.put('sum', 0L); state.data.put(q, cur); } cur.put('count', (long) cur.get('count') + 1); cur.put('sum', (long) cur.get('sum') + rc); } }",
                    'combine_script' => 'return state.data;',
                    'reduce_script' => "Map merged = new HashMap(); for (m in states) { if (m == null) continue; for (e in m.entrySet()) { String q = e.getKey(); Map v = e.getValue(); Map cur = merged.get(q); if (cur == null) { cur = new HashMap(); cur.put('count', 0L); cur.put('sum', 0L); merged.put(q, cur); } cur.put('count', (long) cur.get('count') + (long) v.get('count')); cur.put('sum', (long) cur.get('sum') + (long) v.get('sum')); } } List result = new ArrayList(); for (e in merged.entrySet()) { Map v = e.getValue(); Map o = new HashMap(); o.put('metadata_code', 'product'); o.put('query', e.getKey()); o.put('results_count', v.get('sum')); result.add(o); } return result;",
                ],
            ],
        ];

        return new Transform(
            id: $transformId,
            sourceIndex: $sourceIndex,
            targetIndex: $targetIndex,
            groups: $groups,
            aggregations: $aggregations,
            continuous: true,
            description: "Aggregates {$sourceIndex} into {$targetIndex} (one document per session_uid)",
            schedule: [
                'interval' => [
                    'start_time' => time(),
                    'period' => 1,
                    'unit' => 'Minutes',
                ],
            ],
        );
    }
}
