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

final class Version20250729114500_Add_Install_Data extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add install data';
    }

    public function up(Schema $schema): void
    {
        // Skip default data insertion if existing data is already present,
        // which may occur on projects using fixtures from previous versions.
        if ($this->connection->executeQuery('SELECT * FROM source_field')->rowCount()) {
            return;
        }

        $metadataIds = $this->connection->executeQuery('SELECT entity, id FROM metadata')->fetchAllAssociativeIndexed();

        // Add default product source fields.
        $this->addSql("INSERT INTO source_field (id, metadata_id, code, default_label, type, weight, is_searchable, is_filterable, is_sortable, is_spellchecked, is_used_for_rules, is_system, search, is_used_in_autocomplete, is_spannable, default_search_analyzer) VALUES (nextval('source_field_id_seq'), {$metadataIds['product']['id']}, 'id', NULL, 'reference', 1, NULL, NULL, true, NULL, true, true, 'id Id', NULL, NULL, 'reference')");
        $this->addSql("INSERT INTO source_field (id, metadata_id, code, default_label, type, weight, is_searchable, is_filterable, is_sortable, is_spellchecked, is_used_for_rules, is_system, search, is_used_in_autocomplete, is_spannable, default_search_analyzer) VALUES (nextval('source_field_id_seq'), {$metadataIds['product']['id']}, 'sku', NULL, 'reference', 1, true, NULL, NULL, NULL, true, true, 'sku Sku', NULL, NULL, 'reference')");
        $this->addSql("INSERT INTO source_field (id, metadata_id, code, default_label, type, weight, is_searchable, is_filterable, is_sortable, is_spellchecked, is_used_for_rules, is_system, search, is_used_in_autocomplete, is_spannable, default_search_analyzer) VALUES (nextval('source_field_id_seq'), {$metadataIds['product']['id']}, 'category', NULL, 'category', 1, true, true, true, NULL, true, true, 'category Category', NULL, NULL, 'standard')");
        $this->addSql("INSERT INTO source_field (id, metadata_id, code, default_label, type, weight, is_searchable, is_filterable, is_sortable, is_spellchecked, is_used_for_rules, is_system, search, is_used_in_autocomplete, is_spannable, default_search_analyzer) VALUES (nextval('source_field_id_seq'), {$metadataIds['product']['id']}, 'name', NULL, 'text', 10, true, NULL, true, true, true, true, 'name Name', NULL, NULL, 'standard')");
        $this->addSql("INSERT INTO source_field (id, metadata_id, code, default_label, type, weight, is_searchable, is_filterable, is_sortable, is_spellchecked, is_used_for_rules, is_system, search, is_used_in_autocomplete, is_spannable, default_search_analyzer) VALUES (nextval('source_field_id_seq'), {$metadataIds['product']['id']}, 'price', NULL, 'price', 1, NULL, true, true, NULL, true, true, 'price Price', NULL, NULL, 'standard')");
        $this->addSql("INSERT INTO source_field (id, metadata_id, code, default_label, type, weight, is_searchable, is_filterable, is_sortable, is_spellchecked, is_used_for_rules, is_system, search, is_used_in_autocomplete, is_spannable, default_search_analyzer) VALUES (nextval('source_field_id_seq'), {$metadataIds['product']['id']}, 'image', NULL, 'image', 1, NULL, NULL, NULL, NULL, true, true, 'image Image', NULL, NULL, 'standard')");
        $this->addSql("INSERT INTO source_field (id, metadata_id, code, default_label, type, weight, is_searchable, is_filterable, is_sortable, is_spellchecked, is_used_for_rules, is_system, search, is_used_in_autocomplete, is_spannable, default_search_analyzer) VALUES (nextval('source_field_id_seq'), {$metadataIds['product']['id']}, 'stock', NULL, 'stock', 1, NULL, true, true, NULL, true, true, 'stock Stock', NULL, NULL, 'standard')");
        $this->addSql("INSERT INTO source_field (id, metadata_id, code, default_label, type, weight, is_searchable, is_filterable, is_sortable, is_spellchecked, is_used_for_rules, is_system, search, is_used_in_autocomplete, is_spannable, default_search_analyzer) VALUES (nextval('source_field_id_seq'), {$metadataIds['product']['id']}, 'description', NULL, 'text', 1, true, false, false, true, false, true, 'description Description', NULL, true, 'standard')");

        // Add default category source fields.
        $this->addSql("INSERT INTO source_field (id, metadata_id, code, default_label, type, weight, is_searchable, is_filterable, is_sortable, is_spellchecked, is_used_for_rules, is_system, search, is_used_in_autocomplete, is_spannable, default_search_analyzer) VALUES (nextval('source_field_id_seq'), {$metadataIds['category']['id']}, 'id', NULL, 'text', 1, NULL, NULL, true, NULL, NULL, true, 'id Id', NULL, NULL, 'standard')");
        $this->addSql("INSERT INTO source_field (id, metadata_id, code, default_label, type, weight, is_searchable, is_filterable, is_sortable, is_spellchecked, is_used_for_rules, is_system, search, is_used_in_autocomplete, is_spannable, default_search_analyzer) VALUES (nextval('source_field_id_seq'), {$metadataIds['category']['id']}, 'parentId', NULL, 'text', 1, NULL, NULL, NULL, NULL, NULL, true, 'parentId ParentId', NULL, NULL, 'standard')");
        $this->addSql("INSERT INTO source_field (id, metadata_id, code, default_label, type, weight, is_searchable, is_filterable, is_sortable, is_spellchecked, is_used_for_rules, is_system, search, is_used_in_autocomplete, is_spannable, default_search_analyzer) VALUES (nextval('source_field_id_seq'), {$metadataIds['category']['id']}, 'path', NULL, 'text', 1, NULL, NULL, NULL, NULL, NULL, true, 'path Path', NULL, NULL, 'standard')");
        $this->addSql("INSERT INTO source_field (id, metadata_id, code, default_label, type, weight, is_searchable, is_filterable, is_sortable, is_spellchecked, is_used_for_rules, is_system, search, is_used_in_autocomplete, is_spannable, default_search_analyzer) VALUES (nextval('source_field_id_seq'), {$metadataIds['category']['id']}, 'level', NULL, 'int', 1, NULL, NULL, NULL, NULL, NULL, true, 'level Level', NULL, NULL, 'standard')");
        $this->addSql("INSERT INTO source_field (id, metadata_id, code, default_label, type, weight, is_searchable, is_filterable, is_sortable, is_spellchecked, is_used_for_rules, is_system, search, is_used_in_autocomplete, is_spannable, default_search_analyzer) VALUES (nextval('source_field_id_seq'), {$metadataIds['category']['id']}, 'name', NULL, 'text', 10, true, false, false, true, false, true, 'name Name', NULL, NULL, 'standard')");

        // Add default configuration.
        $this->addSql("INSERT INTO configuration (id, path, value, scope_type, scope_code) VALUES (nextval('configuration_id_seq'), 'gally.analysis.filters', '{\"stemmer\":{\"type\":\"stemmer\",\"params\":{\"language\":\"arabic\"}}}', 'language', 'ar')");
        $this->addSql("INSERT INTO configuration (id, path, value, scope_type, scope_code) VALUES (nextval('configuration_id_seq'), 'gally.analysis.filters', '{\"stemmer\":{\"type\":\"stemmer\",\"params\":{\"language\":\"basque\"}}}', 'language', 'eu')");
        $this->addSql("INSERT INTO configuration (id, path, value, scope_type, scope_code) VALUES (nextval('configuration_id_seq'), 'gally.analysis.filters', '{\"stemmer\":{\"type\":\"stemmer\",\"params\":{\"language\":\"bulgarian\"}}}', 'language', 'bg')");
        $this->addSql("INSERT INTO configuration (id, path, value, scope_type, scope_code) VALUES (nextval('configuration_id_seq'), 'gally.analysis.filters', '{\"stemmer\":{\"type\":\"stemmer\",\"params\":{\"language\":\"catalan\"}},\"elision\":{\"type\":\"elision\",\"params\":{\"articles\":[\"d\",\"l\",\"m\",\"n\",\"s\",\"t\"\]}}}', 'language', 'ca')");
        $this->addSql("INSERT INTO configuration (id, path, value, scope_type, scope_code) VALUES (nextval('configuration_id_seq'), 'gally.analysis.filters', '{\"stemmer\":{\"type\":\"stemmer\",\"params\":{\"language\":\"czech\"}}}', 'language', 'cs')");
        $this->addSql("INSERT INTO configuration (id, path, value, scope_type, scope_code) VALUES (nextval('configuration_id_seq'), 'gally.analysis.filters', '{\"stemmer\":{\"type\":\"stemmer\",\"params\":{\"language\":\"danish\"}}}', 'language', 'da')");
        $this->addSql("INSERT INTO configuration (id, path, value, scope_type, scope_code) VALUES (nextval('configuration_id_seq'), 'gally.analysis.filters', '{\"stemmer\":{\"type\":\"stemmer\",\"params\":{\"language\":\"german2\"}}}', 'language', 'de')");
        $this->addSql("INSERT INTO configuration (id, path, value, scope_type, scope_code) VALUES (nextval('configuration_id_seq'), 'gally.analysis.filters', '{\"stemmer\":{\"type\":\"stemmer\",\"params\":{\"language\":\"english\"}}}', 'language', 'en')");
        $this->addSql("INSERT INTO configuration (id, path, value, scope_type, scope_code) VALUES (nextval('configuration_id_seq'), 'gally.analysis.filters', '{\"stemmer\":{\"type\":\"stemmer\",\"params\":{\"language\":\"spanish\"}}}', 'language', 'es')");
        $this->addSql("INSERT INTO configuration (id, path, value, scope_type, scope_code) VALUES (nextval('configuration_id_seq'), 'gally.analysis.filters', '{\"stemmer\":{\"type\":\"stemmer\",\"params\":{\"language\":\"greek\"}}}', 'language', 'el')");
        $this->addSql("INSERT INTO configuration (id, path, value, scope_type, scope_code) VALUES (nextval('configuration_id_seq'), 'gally.analysis.filters', '{\"stemmer\":{\"type\":\"stemmer\",\"params\":{\"language\":\"finnish\"}}}', 'language', 'fi')");
        $this->addSql("INSERT INTO configuration (id, path, value, scope_type, scope_code) VALUES (nextval('configuration_id_seq'), 'gally.analysis.filters', '{\"stemmer\":{\"type\":\"stemmer\",\"params\":{\"language\":\"french\"}},\"stemmer_override\":{\"type\":\"stemmer_override\",\"params\":{\"rules\":[\"clous => clou\",\"verrous => verrou\",\"ecrous => ecrou\",\"clef => cle\",\"clefs => cle\"]}},\"elision\":{\"type\":\"elision\",\"params\":{\"articles\":[\"l\",\"m\",\"t\",\"qu\",\"n\",\"s\",\"j\",\"d\",\"c\"]}},\"phonetic\":{\"type\":\"phonetic\",\"params\":{\"encoder\":\"beider_morse\",\"languageset\":\"french\"}}}', 'language', 'fr')");
        $this->addSql("INSERT INTO configuration (id, path, value, scope_type, scope_code) VALUES (nextval('configuration_id_seq'), 'gally.analysis.filters', '{\"stemmer\":{\"type\":\"stemmer\",\"params\":{\"language\":\"galician\"}}}', 'language', 'gl')");
        $this->addSql("INSERT INTO configuration (id, path, value, scope_type, scope_code) VALUES (nextval('configuration_id_seq'), 'gally.analysis.filters', '{\"stemmer\":{\"type\":\"stemmer\",\"params\":{\"language\":\"hindi\"}}}', 'language', 'hi')");
        $this->addSql("INSERT INTO configuration (id, path, value, scope_type, scope_code) VALUES (nextval('configuration_id_seq'), 'gally.analysis.filters', '{\"stemmer\":{\"type\":\"stemmer\",\"params\":{\"language\":\"hungarian\"}}}', 'language', 'hu')");
        $this->addSql("INSERT INTO configuration (id, path, value, scope_type, scope_code) VALUES (nextval('configuration_id_seq'), 'gally.analysis.filters', '{\"stemmer\":{\"type\":\"stemmer\",\"params\":{\"language\":\"indonesian\"}}}', 'language', 'id')");
        $this->addSql("INSERT INTO configuration (id, path, value, scope_type, scope_code) VALUES (nextval('configuration_id_seq'), 'gally.analysis.filters', '{\"stemmer\":{\"type\":\"stemmer\",\"params\":{\"language\":\"italian\"}},\"stemmer_override\":{\"type\":\"stemmer_override\",\"params\":{\"rules\":[\"trapani => trap\",\"zanzariere => zanzarier\",\"lavatoi => lavatoi\",\"lamiere => lamier\",\"plafoniere => plafonier\"]}},\"elision\":{\"type\":\"elision\",\"params\":{\"articles\":[\"c\",\"l\",\"all\",\"dall\",\"dell\",\"nell\",\"sull\",\"coll\",\"pell\",\"gl\",\"agl\",\"dagl\",\"degl\",\"negl\",\"sugl\",\"un\",\"m\",\"t\",\"s\",\"v\",\"d\"]}}}', 'language', 'it')");
        $this->addSql("INSERT INTO configuration (id, path, value, scope_type, scope_code) VALUES (nextval('configuration_id_seq'), 'gally.analysis.filters', '{\"stemmer\":{\"type\":\"stemmer\",\"params\":{\"language\":\"latvian\"}}}', 'language', 'lv')");
        $this->addSql("INSERT INTO configuration (id, path, value, scope_type, scope_code) VALUES (nextval('configuration_id_seq'), 'gally.analysis.filters', '{\"stemmer\":{\"type\":\"stemmer\",\"params\":{\"language\":\"lithuanian\"}}}', 'language', 'lt')");
        $this->addSql("INSERT INTO configuration (id, path, value, scope_type, scope_code) VALUES (nextval('configuration_id_seq'), 'gally.analysis.filters', '{\"stemmer\":{\"type\":\"stemmer\",\"params\":{\"language\":\"norwegian\"}}}', 'language', 'nb')");
        $this->addSql("INSERT INTO configuration (id, path, value, scope_type, scope_code) VALUES (nextval('configuration_id_seq'), 'gally.analysis.filters', '{\"stemmer\":{\"type\":\"stemmer\",\"params\":{\"language\":\"light_nynorsk\"}}}', 'language', 'nn')");
        $this->addSql("INSERT INTO configuration (id, path, value, scope_type, scope_code) VALUES (nextval('configuration_id_seq'), 'gally.analysis.filters', '{\"stemmer\":{\"type\":\"stemmer\",\"params\":{\"language\":\"dutch\"}}}', 'language', 'nl')");
        $this->addSql("INSERT INTO configuration (id, path, value, scope_type, scope_code) VALUES (nextval('configuration_id_seq'), 'gally.analysis.filters', '{\"stemmer\":{\"type\":\"stemmer\",\"params\":{\"language\":\"portuguese\"}}}', 'language', 'pt')");
        $this->addSql("INSERT INTO configuration (id, path, value, scope_type, scope_code) VALUES (nextval('configuration_id_seq'), 'gally.analysis.filters', '{\"stemmer\":{\"type\":\"stemmer\",\"params\":{\"language\":\"romanian\"}}}', 'language', 'ro')");
        $this->addSql("INSERT INTO configuration (id, path, value, scope_type, scope_code) VALUES (nextval('configuration_id_seq'), 'gally.analysis.filters', '{\"stemmer\":{\"type\":\"stemmer\",\"params\":{\"language\":\"russian\"}}}', 'language', 'ru')");
        $this->addSql("INSERT INTO configuration (id, path, value, scope_type, scope_code) VALUES (nextval('configuration_id_seq'), 'gally.analysis.filters', '{\"stemmer\":{\"type\":\"stemmer\",\"params\":{\"language\":\"swedish\"}}}', 'language', 'sv')");
        $this->addSql("INSERT INTO configuration (id, path, value, scope_type, scope_code) VALUES (nextval('configuration_id_seq'), 'gally.analysis.filters', '{\"stemmer\":{\"type\":\"stemmer\",\"params\":{\"language\":\"turkish\"}}}', 'language', 'tr')");
    }

    public function down(Schema $schema): void
    {
        echo "Skipping source_fields and configurations deletion. If needed, delete manually with:\n";
        echo "    DELETE FROM source_field WHERE ...;\n";
        echo "    DELETE FROM configuration WHERE ...;\n";
    }
}
