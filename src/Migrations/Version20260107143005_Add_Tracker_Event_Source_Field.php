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

namespace Gally\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260107143005_Add_Tracker_Event_Source_Field extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add tracker event entity source field.';
    }

    public function up(Schema $schema): void
    {
        $metadataIds = $this->connection->executeQuery('SELECT entity, id FROM metadata')->fetchAllAssociativeIndexed();

        $trackingEventSourceFields = [
            'id' => 'keyword',
            '@timestamp' => 'date',
            'event_type' => 'keyword',
            'metadata_code' => 'keyword',
            'localized_catalog_code' => 'keyword',
            'entity_code' => 'keyword',
            'source.event_type' => 'keyword',
            'source.metadata_code' => 'keyword',
            'context.type' => 'keyword',
            'context.code' => 'keyword',
            'session.uid' => 'keyword',
            'session.vid' => 'keyword',
            'group_id' => 'keyword',
            'search_query.is_spellchecked' => 'boolean',
            'search_query.query_text' => 'text',
            'product_list.item_count' => 'integer',
            'product_list.current_page' => 'integer',
            'product_list.page_count' => 'integer',
            'product_list.sort_order' => 'keyword',
            'product_list.sort_direction' => 'keyword',
            'product_list.filters.name' => 'keyword',
            'product_list.filters.value' => 'keyword',
            'cart.qty' => 'integer',
            'display.position' => 'integer',
            'order.order_id' => 'integer',
            'order.total' => 'float',
            'order.price' => 'float',
            'order.qty' => 'float',
            'order.row_total' => 'float',
        ];

        foreach ($trackingEventSourceFields as $code => $type) {
            $this->addInsertTrackingEventSourceFieldSql(
                $metadataIds['tracking_event']['id'], $code, $type
            );
        }
    }

    private function addInsertTrackingEventSourceFieldSql(int $metadataId, string $code, string $type)
    {
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

    public function down(Schema $schema): void
    {
        echo "Skipping source_fields deletion. If needed, delete manually with:\n";
        echo "    DELETE FROM source_field WHERE ...;\n";
    }
}
