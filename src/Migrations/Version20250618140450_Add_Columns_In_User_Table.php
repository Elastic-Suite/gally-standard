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

final class Version20250618140450_Add_Columns_In_User_Table extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add first_name, last_name and is_active columns in user table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD first_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('UPDATE "user" SET first_name = \'first name\' WHERE first_name IS NULL');
        $this->addSql('ALTER TABLE "user" ALTER first_name SET NOT NULL');

        $this->addSql('ALTER TABLE "user" ADD last_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('UPDATE "user" SET last_name = \'last name\' WHERE last_name IS NULL');
        $this->addSql('ALTER TABLE "user" ALTER last_name SET NOT NULL');

        $this->addSql('ALTER TABLE "user" ADD is_active BOOLEAN DEFAULT NULL');
        $this->addSql('UPDATE "user" SET is_active = true WHERE is_active IS NULL');
        $this->addSql('ALTER TABLE "user" ALTER is_active SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" DROP first_name');
        $this->addSql('ALTER TABLE "user" DROP last_name');
        $this->addSql('ALTER TABLE "user" DROP is_active');
    }
}
