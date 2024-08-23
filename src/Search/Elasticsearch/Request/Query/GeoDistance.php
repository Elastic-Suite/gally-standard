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

namespace Gally\Search\Elasticsearch\Request\Query;

use Gally\Search\Elasticsearch\Request\QueryInterface;

/**
 * Gally date range query implementation.
 */
class GeoDistance implements QueryInterface
{
    public const DISTANCE_TYPE_ARC = 'arc';
    public const DISTANCE_TYPE_PLANE = 'plane';
    public const VALIDATION_METHOD_STRICT = 'STRICT';
    public const VALIDATION_METHOD_IGNORE_MALFORMED = 'IGNORE_MALFORMED';
    public const VALIDATION_METHOD_COERCE = 'COERCE';

    private ?string $name;
    private string $field;
    private float $boost;
    private string $distance;
    private string $referenceLocation;
    private string $distanceType;
    private string $validationMethod;

    /**
     * Constructor.
     *
     * @param string      $field             Query field
     * @param string      $distance          Query distance
     * @param string      $referenceLocation Query reference location
     * @param string|null $name              Query name
     * @param float       $boost             Query boost
     */
    public function __construct(
        string $field,
        string $distance,
        string $referenceLocation,
        string $distanceType = self::DISTANCE_TYPE_ARC,
        string $validationMethod = self::VALIDATION_METHOD_STRICT,
        ?string $name = null,
        float $boost = QueryInterface::DEFAULT_BOOST_VALUE
    ) {
        $this->field = $field;
        $this->distance = $distance;
        $this->referenceLocation = $referenceLocation;
        $this->distanceType = $distanceType;
        $this->validationMethod = $validationMethod;
        $this->name = $name;
        $this->boost = $boost;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getBoost(): float
    {
        return $this->boost;
    }

    public function getType(): string
    {
        return QueryInterface::TYPE_GEO_DISTANCE;
    }

    /**
     * Search field.
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * Maximum distance from reference location.
     */
    public function getDistance(): string
    {
        return $this->distance;
    }

    /**
     * Reference point to calculate distance.
     */
    public function getReferenceLocation(): string
    {
        return $this->referenceLocation;
    }

    /**
     * Get distance calculation type.
     */
    public function getDistanceType(): string
    {
        return $this->distanceType;
    }

    /**
     * Get validation method.
     */
    public function getValidationMethod(): string
    {
        return $this->validationMethod;
    }
}
