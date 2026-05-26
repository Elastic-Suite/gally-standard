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

final class Version20260526000000_Make_Name_Mandatory_On_Catalogs extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Make 'name' column NOT NULL on 'catalog' and 'localized_catalog' tables";
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE catalog SET name = code WHERE name IS NULL OR name = ''");
        $this->addSql('ALTER TABLE catalog ALTER name SET NOT NULL');

        $this->addSql("UPDATE localized_catalog SET name = code WHERE name IS NULL OR name = ''");
        $this->addSql('ALTER TABLE localized_catalog ALTER name SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE catalog ALTER name DROP NOT NULL');
        $this->addSql('ALTER TABLE localized_catalog ALTER name DROP NOT NULL');
    }
}
