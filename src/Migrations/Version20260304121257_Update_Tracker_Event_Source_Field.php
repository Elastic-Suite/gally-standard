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
use Gally\Migrations\Trait\TrackingEventSourceFieldPersistorTrait;

final class Version20260304121257_Update_Tracker_Event_Source_Field extends AbstractMigration
{
    use TrackingEventSourceFieldPersistorTrait;

    public function getDescription(): string
    {
        return 'Update tracker event entity source fields';
    }

    public function up(Schema $schema): void
    {
        $sourceFieldsToUpdate = [
            'search_query.query_text' => [
                'is_searchable' => true,
                'is_sortable' => true,
                'is_filterable' => true,
            ],
        ];

        foreach ($sourceFieldsToUpdate as $code => $valuesToUpdate) {
            $this->addUpdateTrackingEventSourceFieldSql($code, $valuesToUpdate);
        }
    }

    public function down(Schema $schema): void
    {
        $sourceFieldsToUpdate = [
            'search_query.query_text' => [
                'is_searchable' => false,
                'is_sortable' => false,
                'is_filterable' => false,
            ],
        ];

        foreach ($sourceFieldsToUpdate as $code => $valuesToUpdate) {
            $this->addUpdateTrackingEventSourceFieldSql($code, $valuesToUpdate);
        }
    }
}
