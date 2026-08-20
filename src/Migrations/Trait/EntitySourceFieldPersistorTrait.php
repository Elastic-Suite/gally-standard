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

trait EntitySourceFieldPersistorTrait
{
    private array $sourceFieldMetadataIds = [];

    private function resolveSourceFieldMetadataId(string $entity): int
    {
        if (!isset($this->sourceFieldMetadataIds[$entity])) {
            $metadataIds = $this->connection->executeQuery('SELECT entity, id FROM metadata')->fetchAllAssociativeIndexed();
            $this->sourceFieldMetadataIds[$entity] = $metadataIds[$entity]['id'];
        }

        return $this->sourceFieldMetadataIds[$entity];
    }

    private function insertSourceFieldSql(string $entity, string $code, string $type): void
    {
        $metadataId = $this->resolveSourceFieldMetadataId($entity);
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

    private function updateSourceFieldSql(string $entity, string $code, array $valuesToUpdate): void
    {
        $metadataId = $this->resolveSourceFieldMetadataId($entity);

        $setClause = implode(', ', array_map(
            fn ($key, $value) => match (\gettype($value)) {
                'boolean' => "$key = " . ($value ? 'true' : 'false'),
                'integer' => "$key = $value",
                'string' => "$key = '$value'",
                default => "$key = null",
            },
            array_keys($valuesToUpdate),
            array_values($valuesToUpdate)
        ));

        $this->addSql("
            UPDATE public.source_field
            SET $setClause
            WHERE code like '$code' AND metadata_id = '$metadataId'
            ;"
        );
    }
}
