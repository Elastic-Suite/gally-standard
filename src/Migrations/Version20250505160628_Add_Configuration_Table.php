<?php

declare(strict_types=1);

namespace Gally\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250505160628_Add_Configuration_Table extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add configuration table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE configuration_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE configuration (id INT NOT NULL, path TEXT NOT NULL, value TEXT DEFAULT NULL, scope_type VARCHAR(255) NOT NULL, scope_code VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE configuration_id_seq CASCADE');
        $this->addSql('DROP TABLE configuration');
    }
}
