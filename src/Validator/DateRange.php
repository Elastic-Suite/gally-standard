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

namespace Gally\Validator;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

class DateRange
{
    public static function validate(object $object, ExecutionContextInterface $context, mixed $payload): void
    {
        if ($object->getFromDate() instanceof \DateTime && $object->getToDate() instanceof \DateTime) {
            if ($object->getFromDate() > $object->getToDate()) {
                $context->buildViolation('gally.validator.error_date_range')
                    ->atPath('fromDate')
                    ->addViolation();
            }
        }
    }
}
