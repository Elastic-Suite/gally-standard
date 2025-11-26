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

namespace Gally\Tracker\MessageHandler;

use Gally\Tracker\Entity\TrackingEvent;
use Symfony\Component\Messenger\Handler\Acknowledger;
use Symfony\Component\Messenger\Handler\BatchHandlerInterface;
use Symfony\Component\Messenger\Handler\BatchHandlerTrait;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class TrackingEventHandler implements BatchHandlerInterface
{
    use BatchHandlerTrait;

    private const BATCH_SIZE = 100;

    public function __construct(
        private iterable $validators,
    ) {
    }

    public function __invoke(TrackingEvent $event, ?Acknowledger $ack = null): mixed
    {
        // Validate message
        $violations = $this->validate($event);
        if ($violations->count() > 0) {
            if ($ack) {
                $ack->nack(new \InvalidArgumentException(
                    \sprintf('Invalid tracking event: %s', $violations)
                ));

                return 0;
            }
            throw new \InvalidArgumentException(\sprintf('Invalid tracking event: %s', $violations));
        }

        $this->handle($event, $ack);

        return \count($this->jobs);
    }

    // Set a custom limit
    protected function shouldFlush(): bool
    {
        return self::BATCH_SIZE <= \count($this->jobs);
    }

    private function process(array $jobs): void
    {
        $originalMessageAckMap = []; // Map original message ID => ack
        $documents = [];

        /**
         * @var TrackingEvent $message
         */
        foreach ($jobs as [$message, $ack]) {
            echo 'Processing event: ' . $message->getId() . \PHP_EOL;
            $originalMessageAckMap[$message->getId()] = $ack;

            $doc = $message->toArray();
            $documents[] = $doc;
        }

        echo 'Bulk size: ' . \count($documents) . \PHP_EOL;

        $response = $this->bulkToOpenSearch($documents);

        // Track which messages failed and nack them
        foreach ($response['items'] as $item) {
            $docId = $item['index']['_id'];

            if (isset($item['index']['error'])) {
                if (isset($originalMessageAckMap[$docId])) {
                    echo 'Rejecting event: ' . $docId . ', error: ' . $item['index']['error']['reason'] . \PHP_EOL;
                    $ack = $originalMessageAckMap[$docId];
                    $ack->nack(new \Exception($item['index']['error']['reason']));
                    unset($originalMessageAckMap[$docId]);
                }
            }
        }

        // Ack remaining messages
        foreach ($originalMessageAckMap as $ack) {
            $ack->ack();
        }
    }

    private function validate(TrackingEvent $message): ConstraintViolationListInterface
    {
        $violations = null;

        foreach ($this->validators as $validator) {
            $result = $validator->validate($message);
            if ($result->count() > 0) {
                $violations = $result;
                break;
            }
        }

        return $violations ?? new \Symfony\Component\Validator\ConstraintViolationList();
    }

    private function bulkToOpenSearch(array $documents): array
    {
        $params = ['body' => []];

        foreach ($documents as $document) {
            $params['body'][] = [
                'index' => [
                    '_index' => 'tracking_events',
                    '_id' => $document['event']['id'],
                ],
            ];
            $params['body'][] = $document;
        }

        return $this->mockBulkResponse($params);
    }

    private function mockBulkResponse(array $params): array
    {
        $items = [];
        $body = $params['body'];

        for ($i = 0; $i < \count($body); $i += 2) {
            $meta = $body[$i]['index'];
            $document = $body[$i + 1];

            $item = [
                'index' => [
                    '_id' => $meta['_id'],
                    '_index' => $meta['_index'],
                ],
            ];

            // Fail if entityCode is 'error'
            if (isset($document['entity_code']) && 'error' === $document['entity_code']) {
                $item['index']['error'] = [
                    'type' => 'invalid_entity_code',
                    'reason' => 'Entity code "error" is not allowed',
                ];
            }

            $items[] = $item;
        }

        return ['items' => $items, 'errors' => \count(array_filter($items, fn ($i) => isset($i['index']['error']))) > 0];
    }
}
