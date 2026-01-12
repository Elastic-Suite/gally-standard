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

namespace Gally\Tracker\Validator;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use Gally\Metadata\Repository\MetadataRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class MetadataValidator extends ConstraintValidator
{
    public function __construct(
        private MetadataRepository $metadataRepository,
    ) {
    }

    /**
     * @param Metadata $constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (null === $value) {
            return;
        }

        try {
            $this->metadataRepository->findByEntity($value);
        } catch (InvalidArgumentException) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ entity }}', $value)
                ->addViolation();
        }
    }
}
