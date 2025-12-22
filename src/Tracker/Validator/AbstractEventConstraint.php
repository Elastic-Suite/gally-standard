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

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class AbstractEventConstraint extends Constraint
{
    public string $missingEntityCodeMessage = 'Tracking event require entityCode field.';
    public string $invalidJsonMessage = 'Payload does not contain valid JSON.';
    public string $missingPayloadFieldMessage = 'The field {{ field }} is missing from payload data.';
    public string $wrongPayloadFieldTypeMessage = 'The value of {{ field }} is not {{ type }}.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
