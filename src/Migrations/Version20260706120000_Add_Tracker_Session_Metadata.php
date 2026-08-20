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
        $this->addSql('ALTER TABLE metadata ADD old_indices_kept BOOLEAN DEFAULT NULL');
        $this->addSql("INSERT INTO metadata (id, entity, is_time_series_data, old_indices_kept) VALUES (nextval('metadata_id_seq'), 'tracking_session', false, true)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE metadata DROP old_indices_kept');
        echo "Skipping metadata deletion. If needed, delete manually with:\n";
        echo "    DELETE FROM metadata WHERE entity = 'tracking_session';\n";
    }
}
