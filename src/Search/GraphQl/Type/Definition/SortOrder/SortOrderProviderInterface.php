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

namespace Gally\Search\GraphQl\Type\Definition\SortOrder;

use Gally\Metadata\Entity\SourceField;

interface SortOrderProviderInterface
{
    /**
     * Returns true if the sort order provider supports the provided source field.
     *
     * @param SourceField $sourceField Source field
     */
    public function supports(SourceField $sourceField): bool;

    /**
     * Get the sort order field name corresponding to the provided source field.
     *
     * @param SourceField $sourceField Source field
     */
    public function getSortOrderField(SourceField $sourceField): string;

    /**
     * Get the sort order detailed label corresponding to the provided source field.
     *
     * @param string $code  Source field code
     * @param string $label Source field label
     */
    public function getLabel(string $code, string $label): string;

    /**
     * Get the sort order simplified label corresponding to the provided source field.
     *
     * @param SourceField $sourceField Source field
     */
    public function getSimplifiedLabel(SourceField $sourceField): string;
}
