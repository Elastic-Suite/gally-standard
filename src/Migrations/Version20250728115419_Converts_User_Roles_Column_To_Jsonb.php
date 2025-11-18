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

final class Version20250728115419_Converts_User_Roles_Column_To_Jsonb extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert the "roles" column from json to jsonb and create a GIN index for performance';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ALTER COLUMN roles TYPE jsonb USING roles::jsonb');
        $this->addSql('CREATE INDEX idx_user_roles_jsonb ON "user" USING GIN (roles)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_user_roles_jsonb');
        $this->addSql('ALTER TABLE "user" ALTER COLUMN roles TYPE json USING roles::json');
    }
}
