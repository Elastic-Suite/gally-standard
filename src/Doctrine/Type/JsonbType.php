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

namespace Gally\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;

/**
 * Custom Doctrine DBAL type for PostgreSQL JSONB columns.
 *
 * This type extends the standard JsonType to provide native JSONB support
 * for PostgreSQL databases, offering better performance and indexing capabilities
 * compared to regular JSON columns.
 */
class JsonbType extends JsonType
{
    public const NAME = 'jsonb';

    /**
     * Returns the SQL declaration snippet for a JSONB column.
     *
     * @param array            $column   The column definition array containing column metadata
     * @param AbstractPlatform $platform The database platform instance
     *
     * @return string The SQL declaration for JSONB type
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'JSONB';
    }

    /**
     * Returns the name of this database type.
     *
     * @return string The type name 'jsonb'
     */
    public function getName(): string
    {
        return self::NAME;
    }
}
