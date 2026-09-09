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

final class Version20260909120000_Add_Is_System_And_Is_Internal_Columns_To_Metadata extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_system and is_internal columns to metadata table and flag product, category and tracking_event metadata accordingly';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE metadata ADD is_system BOOLEAN NOT NULL DEFAULT false');
        $this->addSql('ALTER TABLE metadata ADD is_internal BOOLEAN NOT NULL DEFAULT false');
        $this->addSql("UPDATE metadata SET is_system = true WHERE entity IN ('product', 'category', 'tracking_event')");
        $this->addSql("UPDATE metadata SET is_internal = true WHERE entity = 'tracking_event'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE metadata DROP is_system');
        $this->addSql('ALTER TABLE metadata DROP is_internal');
    }
}
