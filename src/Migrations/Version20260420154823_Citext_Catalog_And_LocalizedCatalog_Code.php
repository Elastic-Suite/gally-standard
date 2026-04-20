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

final class Version20260420154823_Citext_Catalog_And_LocalizedCatalog_Code extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make catalog.code and localized_catalog.code case-insensitive using citext';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE EXTENSION IF NOT EXISTS citext');

        $this->addSql('DROP INDEX UNIQ_1B2C324777153098');
        $this->addSql('ALTER TABLE catalog ALTER COLUMN code TYPE citext');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1B2C324777153098 ON catalog (code)');

        $this->addSql('DROP INDEX UNIQ_DB10491E77153098');
        $this->addSql('ALTER TABLE localized_catalog ALTER COLUMN code TYPE citext');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DB10491E77153098 ON localized_catalog (code)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_1B2C324777153098');
        $this->addSql('ALTER TABLE catalog ALTER COLUMN code TYPE VARCHAR(255)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1B2C324777153098 ON catalog (code)');

        $this->addSql('DROP INDEX UNIQ_DB10491E77153098');
        $this->addSql('ALTER TABLE localized_catalog ALTER COLUMN code TYPE VARCHAR(255)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DB10491E77153098 ON localized_catalog (code)');
    }
}
