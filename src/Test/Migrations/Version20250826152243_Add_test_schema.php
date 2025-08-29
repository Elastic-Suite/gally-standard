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

namespace Gally\Test\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250826152243_Add_test_schema extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add test specific tables';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE test_doctrine_fake_entity_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE test_fake_entity_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE test_fake_locale_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE test_doctrine_fake_entity (id INT NOT NULL, code VARCHAR(255) NOT NULL, name VARCHAR(255) DEFAULT NULL, weight INT DEFAULT NULL, roles JSON NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CA9AFC7677153098 ON test_doctrine_fake_entity (code)');
        $this->addSql('CREATE TABLE test_fake_entity (id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE fake_entity_localized_catalog (fake_entity_id INT NOT NULL, localized_catalog_id INT NOT NULL, PRIMARY KEY(fake_entity_id, localized_catalog_id))');
        $this->addSql('CREATE INDEX IDX_6B55F18521B1F8C9 ON fake_entity_localized_catalog (fake_entity_id)');
        $this->addSql('CREATE INDEX IDX_6B55F1854CF5AFB9 ON fake_entity_localized_catalog (localized_catalog_id)');
        $this->addSql('CREATE TABLE test_fake_locale (id INT NOT NULL, locale VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE fake_entity_localized_catalog ADD CONSTRAINT FK_6B55F18521B1F8C9 FOREIGN KEY (fake_entity_id) REFERENCES test_fake_entity (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE fake_entity_localized_catalog ADD CONSTRAINT FK_6B55F1854CF5AFB9 FOREIGN KEY (localized_catalog_id) REFERENCES localized_catalog (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE test_doctrine_fake_entity_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE test_fake_entity_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE test_fake_locale_id_seq CASCADE');
        $this->addSql('ALTER TABLE fake_entity_localized_catalog DROP CONSTRAINT FK_6B55F18521B1F8C9');
        $this->addSql('ALTER TABLE fake_entity_localized_catalog DROP CONSTRAINT FK_6B55F1854CF5AFB9');
        $this->addSql('DROP TABLE test_doctrine_fake_entity');
        $this->addSql('DROP TABLE test_fake_entity');
        $this->addSql('DROP TABLE fake_entity_localized_catalog');
        $this->addSql('DROP TABLE test_fake_locale');
    }
}
