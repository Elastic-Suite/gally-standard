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

namespace Gally\Product\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class DefaultSortingFieldConstraint extends Constraint
{
    public $message = 'The option "{{ sortOption }}" is not a valid option for sorting.';
}
