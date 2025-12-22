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

use Gally\Job\Entity\Job;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class JobFileConstraintValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        $job = $value;
        if (!$job instanceof Job) {
            throw new UnexpectedTypeException($job, Job::class); // @codeCoverageIgnore
        }

        if (!$constraint instanceof JobFileConstraint) {
            throw new UnexpectedTypeException($constraint, JobFileConstraint::class); // @codeCoverageIgnore
        }

        if (null !== $job->getId()) {
            return; // Skip validation for existing entities
        }

        if (Job::TYPE_EXPORT === $job->getType() && null !== $job->getFile()) {
            $this->context->buildViolation($constraint->exportMessage)
                ->atPath('file')
                ->addViolation();
        }

        if (Job::TYPE_IMPORT === $job->getType() && null === $job->getFile()) {
            $this->context->buildViolation($constraint->importMessage)
                ->atPath('file')
                ->addViolation();
        }
    }
}
