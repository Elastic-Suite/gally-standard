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

        $items = $data->getItems();
        if (\is_string($items)) {
            $items = json_decode($items, true);
        }

        if (empty($items)) {
            $messages[] = $data;
        } else {
            foreach ($items as $item) {
                // Clone event and set entityCode and payload from item
                $clonedData = clone $data;

                // Todo validate entityCode ??
                if (isset($item['entityCode'])) {
                    $clonedData->setEntityCode($item['entityCode']);
                }

                // Merge item data into payload
                $payloadData = [];
                if ($clonedData->getPayload()) {
                    $payloadData = json_decode($clonedData->getPayload(), true) ?? [];
                }

                $itemData = array_diff_key($item, ['entityCode' => null]);
                $payloadData = array_merge_recursive($payloadData, $itemData);
                $clonedData->setPayload(json_encode($payloadData));
                $clonedData->setItems(null);

                $messages[] = $clonedData;
            }
        }

        return $messages;
    }
}
