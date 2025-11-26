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
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class EventTypeValidator implements TrackingEventValidatorInterface
{
    private const VALID_EVENT_TYPES = ['view', 'search', 'add_to_cart', 'display', 'order'];

    public function validate(TrackingEvent $event): ConstraintViolationListInterface
    {
        $violations = new ConstraintViolationList();

        if (empty($event->getEventType())) {
            $violations->add(
                new ConstraintViolation(
                    'Event type is required',
                    'Event type is required',
                    [],
                    $event,
                    'eventType',
                    null
                )
            );
        } elseif (!\in_array($event->getEventType(), self::VALID_EVENT_TYPES, true)) {
            $violations->add(
                new ConstraintViolation(
                    \sprintf('Event type "%s" is not valid. Valid types are: %s', $event->getEventType(), implode(', ', self::VALID_EVENT_TYPES)),
                    'Event type is not valid',
                    [],
                    $event,
                    'eventType',
                    $event->getEventType()
                )
            );
        }

        return $violations;
    }
}
