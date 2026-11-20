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
use Gally\Migrations\Trait\EntitySourceFieldPersistorTrait;

final class Version20261110100500_Add_Behavioral_Data_Source_Field extends AbstractMigration
{
    use EntitySourceFieldPersistorTrait;

    public function getDescription(): string
    {
        return 'Add behavioral data entity source fields';
    }

    public function up(Schema $schema): void
    {
        $behavioralDataSourceFields = [
            'id' => 'keyword',
            'entity_code' => 'keyword',
            'date' => 'date',
            'event_type' => 'keyword',
            'count' => 'int',
            'revenue' => 'float',
        ];

        foreach ($behavioralDataSourceFields as $code => $type) {
            $this->insertSourceFieldSql('behavioral_data', $code, $type);
        }
    }

    public function down(Schema $schema): void
    {
        echo "Skipping source_fields deletion. If needed, delete manually with:\n";
        echo "    DELETE FROM source_field WHERE metadata_id = (SELECT id FROM metadata WHERE entity = 'behavioral_data');\n";
    }
}
