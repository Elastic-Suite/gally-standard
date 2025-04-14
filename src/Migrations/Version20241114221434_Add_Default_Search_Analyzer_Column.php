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
use Gally\Index\Entity\Index\Mapping\FieldInterface;
use Gally\Metadata\Entity\SourceField;

final class Version20241114221434_Add_Default_Search_Analyzer_Column extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Add 'default_search_analyzer' column in 'source_field' table";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE source_field ADD default_search_analyzer VARCHAR(255) DEFAULT NULL');
        $this->addSql(\sprintf("UPDATE source_field SET default_search_analyzer = '%s'", FieldInterface::ANALYZER_STANDARD));
        $this->addSql(\sprintf("UPDATE source_field SET default_search_analyzer = '%s' WHERE type = '%s'", FieldInterface::ANALYZER_REFERENCE, SourceField\Type::TYPE_REFERENCE));
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE source_field DROP default_search_analyzer');
    }
}
