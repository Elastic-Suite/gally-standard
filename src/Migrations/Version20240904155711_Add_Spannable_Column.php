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

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240904155711_Add_Spannable_Column extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Add 'is_spannable' column in 'source_field' table";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE source_field ADD is_spannable BOOLEAN DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE source_field DROP is_spannable');
    }
}
