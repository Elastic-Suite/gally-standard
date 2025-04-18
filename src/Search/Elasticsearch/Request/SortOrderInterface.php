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

namespace Gally\Search\Elasticsearch\Request;

/**
 * Search sort order specification.
 */
interface SortOrderInterface
{
    public const SORT_ASC = 'asc';
    public const SORT_DESC = 'desc';

    public const MISSING_FIRST = '_first';
    public const MISSING_LAST = '_last';

    public const TYPE_STANDARD = 'standardSortOrder';
    public const TYPE_NESTED = 'nestedSortOrder';
    public const TYPE_SCRIPT = 'scriptSortOrder';
    public const TYPE_DISTANCE = 'distanceSortOrder';

    public const SCORE_MODE_MIN = 'min';
    public const SCORE_MODE_MAX = 'max';
    public const SCORE_MODE_SUM = 'sum';
    public const SCORE_MODE_AVG = 'avg';
    public const SCORE_MODE_MED = 'median';

    public const DEFAULT_SORT_NAME = 'relevance';
    public const DEFAULT_SORT_FIELD = '_score';
    public const DEFAULT_SORT_DIRECTION = self::SORT_DESC;

    /**
     * Sort order name.
     */
    public function getName(): ?string;

    /**
     * Field used for sort.
     */
    public function getField(): string;

    /**
     * Sort order direction.
     */
    public function getDirection(): string;

    /**
     * Sort order type.
     */
    public function getType(): string;
}
