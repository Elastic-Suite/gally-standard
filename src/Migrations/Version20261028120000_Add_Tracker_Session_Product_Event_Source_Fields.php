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
use Gally\Migrations\Trait\TrackingSessionSourceFieldPersistorTrait;

final class Version20261028120000_Add_Tracker_Session_Product_Event_Source_Fields extends AbstractMigration
{
    use TrackingSessionSourceFieldPersistorTrait;

    public function getDescription(): string
    {
        return 'Add tracker session product_view, product_cart and product_order source fields';
    }

    public function up(Schema $schema): void
    {
        foreach (['product_view', 'product_cart', 'product_order'] as $code) {
            $this->addInsertTrackingSessionSourceFieldSql($code, 'keyword');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            "DELETE FROM source_field WHERE code IN ('product_view', 'product_cart', 'product_order') AND metadata_id = {$this->getTrackingSessionMetadataId()}"
        );
    }
}
