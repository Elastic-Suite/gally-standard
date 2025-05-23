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

namespace Gally\Search\Elasticsearch\Builder\Request\SortOrder;

use Gally\Search\Elasticsearch\Request\SortOrderInterface;

/**
 * Normal sort order implementation.
 */
class Standard implements SortOrderInterface
{
    private ?string $name;

    private ?string $field;

    private string $direction;

    private string $missing;

    /**
     * Constructor.
     *
     * @param string  $field     Sort order field
     * @param ?string $direction Sort order direction
     * @param ?string $name      Sort order name
     * @param ?string $missing   How to treat missing values
     */
    public function __construct(string $field, ?string $direction = self::SORT_ASC, ?string $name = null, ?string $missing = null)
    {
        $this->field = $field;
        $this->direction = $direction;
        $this->name = $name;
        $this->missing = $missing ?? (self::SORT_ASC === $direction ? self::MISSING_LAST : self::MISSING_FIRST);
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getDirection(): string
    {
        return $this->direction ?? self::SORT_ASC;
    }

    public function getType(): string
    {
        return SortOrderInterface::TYPE_STANDARD;
    }

    public function getMissing(): string
    {
        return $this->missing;
    }
}
