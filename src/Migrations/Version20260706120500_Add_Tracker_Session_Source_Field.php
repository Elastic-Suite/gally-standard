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

final class Version20260706120500_Add_Tracker_Session_Source_Field extends AbstractMigration
{
    use TrackingSessionSourceFieldPersistorTrait;

    public function getDescription(): string
    {
        return 'Add tracker session entity source field';
    }

    public function up(Schema $schema): void
    {
        $trackingSessionSourceFields = [
            'id' => 'keyword',
            '@timestamp' => 'date',
            'localized_catalog_code' => 'keyword',
            'start_time' => 'date',
            'end_time' => 'date',
            'searches.metadata_code' => 'keyword',
            'searches.query' => 'keyword',
            'searches.results_count' => 'integer',
            'views.metadata_code' => 'keyword',
            'views.count' => 'integer',
            'views.items' => 'keyword',
            'cart.metadata_code' => 'keyword',
            'cart.count' => 'integer',
            'cart.items' => 'keyword',
            'order.metadata_code' => 'keyword',
            'order.count' => 'integer',
            'order.items' => 'keyword',
            'group_id' => 'keyword',
            'session_uid' => 'keyword',
            'session_vid' => 'keyword',
            'ab_campaigns.id' => 'integer',
            'ab_campaigns.scenario' => 'keyword',
        ];

        foreach ($trackingSessionSourceFields as $code => $type) {
            $this->addInsertTrackingSessionSourceFieldSql($code, $type);
        }
    }

    public function down(Schema $schema): void
    {
        echo "Skipping source_fields deletion. If needed, delete manually with:\n";
        echo "    DELETE FROM source_field WHERE metadata_id = (SELECT id FROM metadata WHERE entity = 'tracking_session');\n";
    }
}
