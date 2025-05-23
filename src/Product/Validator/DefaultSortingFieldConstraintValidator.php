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

use ApiPlatform\Metadata\GetCollection;
use Gally\Product\State\ProductSortingOptionProvider;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class DefaultSortingFieldConstraintValidator extends ConstraintValidator
{
    public function __construct(
        private ProductSortingOptionProvider $sortingOptionProvider,
    ) {
    }

    /**
     * @param string $value
     *
     * @return void
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof DefaultSortingFieldConstraint) {
            throw new UnexpectedTypeException($constraint, DefaultSortingFieldConstraint::class); // @codeCoverageIgnore
        }

        $sortOptions = array_column(
            $this->sortingOptionProvider->provide(new GetCollection(), [], ['filters' => ['entityType' => 'product']]),
            'code'
        );

        if (null != $value && !\in_array($value, $sortOptions, true)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ sortOption }}', $value)
                ->addViolation();
        }
    }
}
