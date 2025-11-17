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

final class Version20250521123158_Add_Password_Token_Table extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Add 'password_token' table";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE "password_token_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE "password_token" (id INT NOT NULL, user_id INT NOT NULL, token VARCHAR(50) NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BEAB6C245F37A13B ON "password_token" (token)');
        $this->addSql('CREATE INDEX IDX_BEAB6C24A76ED395 ON "password_token" (user_id)');
        $this->addSql('ALTER TABLE "password_token" ADD CONSTRAINT FK_BEAB6C24A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE "password_token_id_seq" CASCADE');
        $this->addSql('ALTER TABLE "password_token" DROP CONSTRAINT FK_BEAB6C24A76ED395');
        $this->addSql('DROP TABLE "password_token"');
    }
}
