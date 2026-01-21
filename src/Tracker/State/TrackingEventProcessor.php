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

namespace Gally\Tracker\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Gally\Metadata\Service\PriceGroupProvider;
use Gally\Tracker\Entity\TrackingEvent;
use Symfony\Component\Messenger\MessageBusInterface;

class TrackingEventProcessor implements ProcessorInterface
{
    public function __construct(
        private MessageBusInterface $bus,
        private PriceGroupProvider $priceGroupProvider,
    ) {
    }

    /**
     * @param TrackingEvent $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $data->setGroupId($this->priceGroupProvider->getCurrentPriceGroupId());

        // Split items if present
        $messages = $this->splitEvent($data);

        foreach ($messages as $message) {
            $this->bus->dispatch($message);
        }

        return $data;
    }

    private function splitEvent(TrackingEvent $data): array
    {
        $messages = [];

        $payload = $data->getPayload();
        if (\is_string($payload)) {
            $payload = json_decode($payload, true);
            $items = $payload['items'] ?? [];
            unset($payload['items']);
        }

        if (empty($items)) {
            $messages[] = $data;
        } else {
            foreach ($items as $item) {
                // Clone event and set entityCode and payload from item
                $clonedData = clone $data;

                if (isset($item['entityCode'])) {
                    $clonedData->setEntityCode($item['entityCode']);
                    unset($item['entityCode']);
                }

                $payloadData = array_merge_recursive($payload, $item);
                $clonedData->setPayload(json_encode($payloadData));

                $messages[] = $clonedData;
            }
        }

        return $messages;
    }
}
