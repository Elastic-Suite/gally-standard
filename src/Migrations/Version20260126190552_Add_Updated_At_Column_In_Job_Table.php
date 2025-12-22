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

final class Version20260126190552_Add_Updated_At_Column_In_Job_Table extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Add column 'updated_at' in 'job' table";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE job ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('UPDATE job SET updated_at = NOW() WHERE updated_at IS NULL');
        $this->addSql('ALTER TABLE job ALTER updated_at SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE job DROP updated_at');
    }
}
