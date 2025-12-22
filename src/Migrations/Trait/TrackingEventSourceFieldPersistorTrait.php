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

namespace Gally\Migrations\Trait;

trait TrackingEventSourceFieldPersistorTrait
{
    private $metadataId;

    public function getTrackingEventMetadataId(): int
    {
        if (!isset($this->metadataId)) {
            $metadataIds = $this->connection->executeQuery('SELECT entity, id FROM metadata')->fetchAllAssociativeIndexed();
            $this->metadataId = $metadataIds['tracking_event']['id'];
        }

        return $this->metadataId;
    }

    public function addInsertTrackingEventSourceFieldSql(string $code, string $type): void
    {
        $metadataId = $this->getTrackingEventMetadataId();
        $this->addSql("
            INSERT INTO public.source_field
            (id, metadata_id, code, default_label, type, weight, is_searchable, is_filterable, is_sortable, is_spellchecked, is_used_for_rules, is_system, search, is_used_in_autocomplete, is_spannable, default_search_analyzer)
            VALUES (
                nextval('source_field_id_seq'),
                $metadataId,
                '$code',
                null,
                '$type',
                1,
                null,
                null,
                null,
                null,
                null,
                true,
                '$code',
                null,
                null,
                'standard'
            );"
        );
    }
}
