<?php

declare(strict_types=1);

namespace Gally\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251010143715 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE job_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE job_import_file_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE job_log_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE job (id INT NOT NULL, import_file_id INT NOT NULL, type VARCHAR(255) NOT NULL, profile VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, finished_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FBD8E0F880DBD080 ON job (import_file_id)');
        $this->addSql('CREATE TABLE job_import_file (id INT NOT NULL, file_path VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE job_log (id INT NOT NULL, job_id INT NOT NULL, logged_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, severity VARCHAR(255) NOT NULL, log TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_4BEAFB54BE04EA9 ON job_log (job_id)');
        $this->addSql('ALTER TABLE job ADD CONSTRAINT FK_FBD8E0F880DBD080 FOREIGN KEY (import_file_id) REFERENCES job_import_file (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE job_log ADD CONSTRAINT FK_4BEAFB54BE04EA9 FOREIGN KEY (job_id) REFERENCES job (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE job_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE job_import_file_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE job_log_id_seq CASCADE');
        $this->addSql('ALTER TABLE job DROP CONSTRAINT FK_FBD8E0F880DBD080');
        $this->addSql('ALTER TABLE job_log DROP CONSTRAINT FK_4BEAFB54BE04EA9');
        $this->addSql('DROP TABLE job');
        $this->addSql('DROP TABLE job_import_file');
        $this->addSql('DROP TABLE job_log');
    }
}
