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

use Gally\Tracker\Entity\TrackingEvent;

#[\Attribute]
class EventProductDisplayValidator extends AbstractEventValidator
{
    public function getEventType(): string
    {
        return 'display';
    }

    public function getMetadataCode(): string
    {
        return 'product';
    }

    protected function checkData(TrackingEvent $event, AbstractEventConstraint $constraint): void
    {
        $this->validateEntityCode($event, $constraint);
        $payload = $this->getPayload($event, $constraint);

        if (!$this->validateDataType($payload, ['display' => 'array'], $constraint)) {
            return;
        }

        $this->validateDataType($payload['display'], ['position' => 'integer'], $constraint);
    }
}
