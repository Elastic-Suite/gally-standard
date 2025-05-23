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

namespace Gally\Search\Elasticsearch\Request\Aggregation;

/**
 * Catalog Product Search Request coverage.
 */
class Coverage
{
    public function __construct(
        private array $countByAttributeCode,
        private int $size,
    ) {
    }

    /**
     * Load the product count by attribute code.
     */
    public function getProductCountByAttributeCode(): array
    {
        return $this->countByAttributeCode;
    }

    /**
     * Get total count.
     */
    public function getSize(): int
    {
        return $this->size;
    }
}
