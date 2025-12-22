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
class EventOrderValidator extends AbstractEventValidator
{
    public function getEventType(): string
    {
        return 'order';
    }

    public function getMetadataCode(): string
    {
        return 'product';
    }

    protected function checkData(TrackingEvent $event, AbstractEventConstraint $constraint): void
    {
        $this->validateEntityCode($event, $constraint);

        $payload = $this->getPayload($event, $constraint);
        if (!$this->validateDataType($payload, ['order' => 'array'], $constraint)) {
            return;
        }

        $this->validateDataType(
            $payload['order'],
            ['order_id' => 'string', 'total' => 'numeric', 'price' => 'numeric', 'qty' => 'numeric', 'row_total' => 'numeric'],
            $constraint
        );
    }
}
