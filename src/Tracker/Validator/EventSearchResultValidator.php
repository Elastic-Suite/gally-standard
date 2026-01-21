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
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

#[\Attribute]
class EventSearchResultValidator extends AbstractEventValidator
{
    public function getEventType(): string
    {
        return 'search';
    }

    public function getMetadataCode(): string
    {
        return 'product';
    }

    protected function checkData(TrackingEvent $event, Constraint $constraint): void
    {
        if (!$constraint instanceof EventSearchResult) {
            throw new UnexpectedValueException($constraint, EventSearchResult::class);
        }

        if ($event->getEntityCode()) {
            $this->context
                ->buildViolation($constraint->entityCodeAtTopLevelMessage)
                ->atPath('entityCode')
                ->addViolation();
        }

        $payload = $this->getPayload($event, $constraint);
        $this->validateProductListData($payload, $constraint);

        if (!$this->validateDataType($payload, ['search_query' => 'array'], $constraint)) {
            return;
        }

        $this->validateDataType(
            $payload['search_query'],
            ['is_spellchecked' => 'bool', 'query_text' => 'string'],
            $constraint
        );
    }
}
