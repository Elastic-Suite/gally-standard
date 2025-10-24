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
 * Add boolean_logic to facet configuration table.
 */
final class Version20251024122934_AddBooleanLogicToFacetConfiguration extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add boolean_logic to facet configuration table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE facet_configuration ADD boolean_logic VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE facet_configuration DROP boolean_logic');
    }
}
