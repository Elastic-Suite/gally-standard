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
use Gally\Job\Service\JobManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class JobProfileConstraintValidator extends ConstraintValidator
{
    public function __construct(
        private JobManager $jobManager,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        $job = $value;
        if (!$job instanceof Job) {
            throw new UnexpectedTypeException($job, Job::class); // @codeCoverageIgnore
        }

        if (!$constraint instanceof JobProfileConstraint) {
            throw new UnexpectedTypeException($constraint, JobProfileConstraint::class); // @codeCoverageIgnore
        }

        $profiles = $this->jobManager->getProfiles();
        if ((Job::TYPE_IMPORT === $job->getType() && !isset($profiles[Job::TYPE_IMPORT][$job->getProfile()]))
            || (Job::TYPE_EXPORT === $job->getType() && !isset($profiles[Job::TYPE_EXPORT][$job->getProfile()]))
        ) {
            $this->context->buildViolation($constraint->message)
                ->atPath('profile')
                ->setParameter('{{ profile }}', $job->getProfile())
                ->setParameter('{{ type }}', $job->getType())
                ->addViolation();
        }
    }
}
