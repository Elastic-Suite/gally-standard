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
class JobFileConstraint extends Constraint
{
    public string $exportMessage = 'gally.job.file.not_valid';
    public string $importMessage = 'gally.job.file.required';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
