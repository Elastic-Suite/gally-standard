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

final class Version20260107133005_Add_Tracker_Event_Metadata extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add tracker event entity and time series capability';
    }

    public function up(Schema $schema): void
    {
        // Add is_time_series_data in metadata table.
        $this->addSql('ALTER TABLE metadata ADD is_time_series_data BOOLEAN DEFAULT NULL');

        // Add tracking_event metadata.
        $this->addSql("INSERT INTO metadata (id, entity, is_time_series_data) VALUES (nextval('metadata_id_seq'), 'tracking_event', true)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE metadata DROP is_time_series_data');
        echo "Skipping metadata deletion. If needed, delete manually with:\n";
        echo "    DELETE FROM metadata WHERE ...;\n";
    }
}
