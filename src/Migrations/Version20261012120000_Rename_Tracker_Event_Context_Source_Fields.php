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
use Gally\Migrations\Trait\TrackingEventSourceFieldPersistorTrait;

final class Version20261012120000_Rename_Tracker_Event_Context_Source_Fields extends AbstractMigration
{
    use TrackingEventSourceFieldPersistorTrait;

    public function getDescription(): string
    {
        return 'Rename tracker event context source fields to match the indexed context_type and context_code keys';
    }

    public function up(Schema $schema): void
    {
        $this->renameContextSourceFields(['context.type' => 'context.context_type', 'context.code' => 'context.context_code']);
    }

    public function down(Schema $schema): void
    {
        $this->renameContextSourceFields(['context.context_type' => 'context.type', 'context.context_code' => 'context.code']);
    }

    private function renameContextSourceFields(array $codeMapping): void
    {
        foreach ($codeMapping as $currentCode => $newCode) {
            $this->addUpdateTrackingEventSourceFieldSql($currentCode, ['code' => $newCode, 'search' => $newCode]);
        }
    }
}
