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
use Gally\Exception\LogicException;
use Gally\Index\Api\IndexSettingsInterface;
use Gally\Index\Repository\Index\IndexRepositoryInterface;
use Gally\Index\Repository\Transform\TransformRepositoryInterface;

/**
 * Builds and provisions the tracking_session-specific OpenSearch Transform definition for a
 * localized catalog. All the generic OpenSearch Transform plumbing (create/update/start/stop)
 * lives in TransformRepositoryInterface, which is entity-agnostic and reusable for any other
 * future Transform-based aggregation in Gally.
 */
class SessionTransformProvisioner
{
    private const TRANSFORM_ID_PREFIX = 'tracking_session_';

    public function __construct(
        private TransformRepositoryInterface $transformRepository,
        private IndexRepositoryInterface $indexRepository,
        private IndexSettingsInterface $indexSettings,
    ) {
    }

    public function createOrUpdate(LocalizedCatalog $localizedCatalog): string
    {
        $sourceIndex = $this->indexSettings->getIndexAliasFromIdentifier('tracking_event', $localizedCatalog);
        $targetAlias = $this->indexSettings->getIndexAliasFromIdentifier('tracking_session', $localizedCatalog);
        $targetIndex = $this->indexRepository->findByName($targetAlias)?->getName();

        if (null === $targetIndex) {
            throw new LogicException("No tracking_session index installed yet for catalog '{$localizedCatalog->getCode()}': create/install it before provisioning the transform.");
        }

        $transformId = self::TRANSFORM_ID_PREFIX . $localizedCatalog->getCode();

        $this->transformRepository->createOrUpdate($transformId, $this->buildDefinition($sourceIndex, $targetIndex));
        $this->transformRepository->start($transformId);

        return $transformId;
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

    private function buildDefinition(string $sourceIndex, string $targetIndex): array
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

        return [
            'enabled' => true,
            'continuous' => true,
            'schedule' => [
                'interval' => [
                    'start_time' => time(),
                    'period' => 1,
                    'unit' => 'Minutes',
                ],
            ],
            'description' => "Aggregates {$sourceIndex} into {$targetIndex} (one document per session_uid)",
            'source_index' => $sourceIndex,
            'target_index' => $targetIndex,
            'page_size' => 1000,
            'groups' => [
                ['terms' => ['source_field' => 'session_uid', 'target_field' => 'session_uid']],
            ],
            'aggregations' => [
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
            ],
        ];
    }
}
