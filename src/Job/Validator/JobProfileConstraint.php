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

namespace Gally\Job\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class JobProfileConstraint extends Constraint
{
    public $message = 'gally.job.profile.not_valid';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
