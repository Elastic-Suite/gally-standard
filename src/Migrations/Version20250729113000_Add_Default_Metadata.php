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

final class Version20250729113000_Add_Default_Metadata extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add default sample data';
    }

    public function up(Schema $schema): void
    {
        // Skip default metadata insertion if existing data is already present,
        // which may occur on projects using fixtures from previous versions.
        if ($this->connection->executeQuery('SELECT * FROM metadata')->rowCount()) {
            return;
        }

        // Add default metadata.
        $this->addSql("INSERT INTO metadata (id, entity) VALUES (nextval('metadata_id_seq'), 'product')");
        $this->addSql("INSERT INTO metadata (id, entity) VALUES (nextval('metadata_id_seq'), 'category')");
    }

    public function down(Schema $schema): void
    {
        echo "Skipping metadata deletion. If needed, delete manually with:\n";
        echo "    DELETE FROM metadata WHERE ...;\n";
    }
}
