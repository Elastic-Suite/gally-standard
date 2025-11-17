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

final class Version20230727115502_Update_Product_Id_Type extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update product id type';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE category_product_merchandising ALTER product_id TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE category_product_merchandising ALTER product_id DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE category_product_merchandising ALTER product_id TYPE INT');
        $this->addSql('ALTER TABLE category_product_merchandising ALTER product_id DROP DEFAULT');
    }
}
