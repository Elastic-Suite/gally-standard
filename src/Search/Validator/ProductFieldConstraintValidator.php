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

namespace Gally\Search\Validator;

use Gally\Metadata\Entity\SourceField;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ProductFieldConstraintValidator extends ConstraintValidator
{
    /**
     * @param ?SourceField $value
     *
     * @return void
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof ProductFieldConstraint) {
            throw new UnexpectedTypeException($constraint, ProductFieldConstraint::class);
        }

        if ($value && 'product' !== $value->getMetadata()->getEntity()) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ sourceFieldCode }}', $value->getCode())
                ->addViolation();
        }
    }
}
