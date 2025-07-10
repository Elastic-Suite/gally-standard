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

final class Version20250709141342_Fix_category_column_type extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix category tables column type';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE category ALTER path TYPE TEXT');
        $this->addSql('ALTER TABLE category_configuration ALTER name TYPE TEXT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE category_configuration ALTER name TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE category ALTER path TYPE VARCHAR(255)');
    }
}
