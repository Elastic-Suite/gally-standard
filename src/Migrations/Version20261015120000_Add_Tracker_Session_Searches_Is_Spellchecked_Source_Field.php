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

final class Version20261015120000_Add_Tracker_Session_Searches_Is_Spellchecked_Source_Field extends AbstractMigration
{
    use TrackingSessionSourceFieldPersistorTrait;

    public function getDescription(): string
    {
        return 'Add tracker session searches.is_spellchecked source field';
    }

    public function up(Schema $schema): void
    {
        $this->addInsertTrackingSessionSourceFieldSql('searches.is_spellchecked', 'boolean');
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            "DELETE FROM source_field WHERE code = 'searches.is_spellchecked' AND metadata_id = {$this->getTrackingSessionMetadataId()}"
        );
    }
}
