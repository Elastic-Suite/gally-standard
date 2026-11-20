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

final class Version20261110100000_Add_Behavioral_Data_Metadata extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add behavioral data entity';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO metadata (id, entity, is_time_series_data, is_system, is_internal, old_indices_kept) VALUES (nextval('metadata_id_seq'), 'behavioral_data', false, true, true, true)");
    }

    public function down(Schema $schema): void
    {
        echo "Skipping metadata deletion. If needed, delete manually with:\n";
        echo "    DELETE FROM metadata WHERE entity = 'behavioral_data';\n";
    }
}
