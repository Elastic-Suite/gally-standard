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

final class Version20251107170527_Add_Job_Tables extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add job tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE job_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE job_file_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE job_log_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE job (id INT NOT NULL, file_id INT DEFAULT NULL, type VARCHAR(255) NOT NULL, profile VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, finished_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FBD8E0F893CB796C ON job (file_id)');
        $this->addSql('CREATE TABLE job_file (id INT NOT NULL, file_path VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE job_log (id INT NOT NULL, job_id INT NOT NULL, logged_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, severity VARCHAR(255) NOT NULL, message TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_4BEAFB54BE04EA9 ON job_log (job_id)');
        $this->addSql('ALTER TABLE job ADD CONSTRAINT FK_FBD8E0F893CB796C FOREIGN KEY (file_id) REFERENCES job_file (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE job_log ADD CONSTRAINT FK_4BEAFB54BE04EA9 FOREIGN KEY (job_id) REFERENCES job (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE job_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE job_file_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE job_log_id_seq CASCADE');
        $this->addSql('ALTER TABLE job DROP CONSTRAINT FK_FBD8E0F893CB796C');
        $this->addSql('ALTER TABLE job_log DROP CONSTRAINT FK_4BEAFB54BE04EA9');
        $this->addSql('DROP TABLE job');
        $this->addSql('DROP TABLE job_file');
        $this->addSql('DROP TABLE job_log');
    }
}
