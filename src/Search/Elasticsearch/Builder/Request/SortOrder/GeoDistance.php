<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @package   Gally
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Gally\Search\Elasticsearch\Builder\Request\SortOrder;

use Gally\Search\Elasticsearch\Request\SortOrderInterface;

/**
 * Normal sort order implementation.
 */
class GeoDistance implements SortOrderInterface
{
    /**
     * Constant for Geo distance field.
     */
    public const GEO_DISTANCE_FIELD = '_geo_distance';

    private ?string $name;
    private ?string $field;
    private string $referenceLocation;
    private string $direction;
    private string $unit;
    private string $mode;
    private string $distanceType;
    private bool $ignoreUnmapped;

    /**
     * Constructor.
     *
     * @param string  $field             Sort order field
     * @param string  $referenceLocation Reference location
     * @param string  $direction         Sort order direction
     * @param string  $unit              Distance unit
     * @param string  $mode              Distance calculation mode
     * @param string  $distanceType      Distance calculation type
     * @param bool    $ignoreUnmapped    Ignore unmapped field value
     * @param ?string $name              Sort order name
     */
    public function __construct(
        string $field,
        string $referenceLocation,
        string $direction = self::SORT_ASC,
        string $unit = 'km',
        string $mode = 'min',
        string $distanceType = 'arc',
        bool $ignoreUnmapped = false,
        ?string $name = null,
    ) {
        $this->field = $field;
        $this->referenceLocation = $referenceLocation;
        $this->direction = $direction;
        $this->unit = $unit;
        $this->mode = $mode;
        $this->distanceType = $distanceType;
        $this->ignoreUnmapped = $ignoreUnmapped;
        $this->name = $name;
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * Get reference location.
     */
    public function getReferenceLocation(): string
    {
        return $this->referenceLocation;
    }

    /**
     * {@inheritDoc}
     */
    public function getDirection(): string
    {
        return $this->direction ?? self::SORT_ASC;
    }

    /**
     * Get distance unit.
     */
    public function getUnit(): string
    {
        return $this->unit;
    }

    /**
     * Get distance calculation mode.
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * Get distance calculation type.
     */
    public function getDistanceType(): string
    {
        return $this->distanceType;
    }

    /**
     * Get ignore unmapped field value.
     */
    public function getIgnoreUnmapped(): bool
    {
        return $this->ignoreUnmapped;
    }

    /**
     * {@inheritDoc}
     */
    public function getType(): string
    {
        return SortOrderInterface::TYPE_DISTANCE;
    }
}
