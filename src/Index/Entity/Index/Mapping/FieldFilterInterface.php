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

namespace Gally\Index\Entity\Index\Mapping;

/**
 * An interface that allowed to specify a field filter.
 */
interface FieldFilterInterface
{
    /**
     * Indicates if the field has to be added to the list or not.
     *
     * @param FieldInterface $field field to be tested
     */
    public function filterField(FieldInterface $field): bool;
}
