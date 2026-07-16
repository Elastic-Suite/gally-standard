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

final class Version20260706120000_Add_Tracker_Session_Metadata extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add tracker session entity';
    }

    public function up(Schema $schema): void
    {
        // Not a time-series entity: tracking_session is a regular, reindexable index
        // (one document per session, upserted by the OpenSearch Transform), unlike
        // tracking_event which is an append-only data stream.
        $this->addSql("INSERT INTO metadata (id, entity, is_time_series_data) VALUES (nextval('metadata_id_seq'), 'tracking_session', false)");
    }

    public function down(Schema $schema): void
    {
        echo "Skipping metadata deletion. If needed, delete manually with:\n";
        echo "    DELETE FROM metadata WHERE entity = 'tracking_session';\n";
    }
}
