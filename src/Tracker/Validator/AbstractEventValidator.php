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
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

abstract class AbstractEventValidator extends ConstraintValidator
{
    abstract public function getEventType(): string;

    abstract public function getMetadataCode(): string;

    /**
     * @param TrackingEvent $value
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$value instanceof TrackingEvent) {
            throw new UnexpectedValueException($value, TrackingEvent::class);
        }

        if (!$constraint instanceof AbstractEventConstraint) {
            throw new UnexpectedValueException($constraint, AbstractEventConstraint::class);
        }

        if ($value->getEventType() !== $this->getEventType()
            || $value->getMetadataCode() !== $this->getMetadataCode()) {
            return;
        }

        $this->checkData($value, $constraint);
    }

    abstract protected function checkData(TrackingEvent $event, AbstractEventConstraint $constraint);

    protected function validateEntityCode(TrackingEvent $event, AbstractEventConstraint $constraint)
    {
        if (!$event->getEntityCode()) {
            $this->context
                ->buildViolation($constraint->missingEntityCodeMessage)
                ->atPath('entityCode')
                ->addViolation();
        }
    }

    protected function getPayload($value, AbstractEventConstraint $constraint): ?array
    {
        $payload = json_decode($value->getPayload(), true);

        if (\JSON_ERROR_NONE !== json_last_error()) {
            $this->context->buildViolation($constraint->invalidJsonMessage)
                ->setParameter('{{ error }}', 'Invalid JSON')
                ->addViolation();

            return null;
        }

        return $payload;
    }

    protected function validateDataType(array $data, array $expectedTypes, AbstractEventConstraint $constraint): bool
    {
        $isValid = true;
        foreach ($expectedTypes as $field => $type) {
            if (!isset($data[$field])) {
                $isValid = false;
                $this->context->buildViolation($constraint->missingPayloadFieldMessage)
                    ->setParameter('{{ field }}', $field)
                    ->addViolation();
                continue;
            }

            $checkMethod = 'is_' . $type;
            if (!$checkMethod($data[$field])) {
                $isValid = false;
                $this->context->buildViolation($constraint->wrongPayloadFieldTypeMessage)
                    ->setParameter('{{ field }}', $field)
                    ->setParameter('{{ type }}', $type)
                    ->addViolation();
            }
        }

        return $isValid;
    }

    protected function validateProductListData(array $data, AbstractEventConstraint $constraint): void
    {
        if (!$this->validateDataType($data, ['product_list' => 'array'], $constraint)) {
            return;
        }

        $expectedTypes = [
            'item_count' => 'integer',
            'current_page' => 'integer',
            'page_count' => 'integer',
            'sort_order' => 'string',
            'sort_direction' => 'string',
            'filters' => 'array',
        ];
        if (!$this->validateDataType($data['product_list'], $expectedTypes, $constraint)) {
            return;
        }

        foreach ($data['product_list']['filters'] as $filter) {
            $this->validateDataType($filter, ['name' => 'string', 'value' => 'string'], $constraint);
        }
    }
}
