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

use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Index\Dto\DataStreamBulk;
use Gally\Index\Entity\DataStream;
use Gally\Index\Repository\DataStream\DataStreamRepositoryInterface;
use Gally\Metadata\Repository\MetadataRepository;
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
        private DataStreamRepositoryInterface $dataStreamRepository,
        private MetadataRepository $metadataRepository,
        private LocalizedCatalogRepository $localizedCatalogRepository,
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

    protected function shouldFlush(): bool
    {
        return self::BATCH_SIZE <= \count($this->jobs);
    }

    private function process(array $jobs): void
    {
        $originalMessageAckMap = [];
        $bulkRequest = new DataStreamBulk\Request();

        /** @var TrackingEvent $message */
        foreach ($jobs as [$message, $ack]) {
            echo 'Processing event: ' . $message->getId() . \PHP_EOL;
            $originalMessageAckMap[$message->getId()] = $ack;
            $dataStream = $this->getDataStream($message);
            $bulkRequest->addDocument($dataStream, $message->toArray());
        }

        echo 'Bulk size: ' . \count($bulkRequest->getOperations()) . \PHP_EOL;
        $response = $this->dataStreamRepository->bulk($bulkRequest);

        if ($response->hasErrors()) {
            foreach ($response->aggregateErrorsByReason() as $error) {
                echo 'Bulk error: ' . $error['reason'] . ' (count: ' . $error['count'] . ')' . \PHP_EOL;
            }

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

    private function getDataStream(TrackingEvent $event): DataStream
    {
        $localizedCatalog = $this->localizedCatalogRepository->findByCodeOrId($event->getLocalizedCatalogCode());
        // Todo manage missing localize dcatalog

        $metadata = $this->metadataRepository->findOneBy(['entity' => 'tracking_event']);

        $dataStream = $this->dataStreamRepository->findByMetadata($metadata, $localizedCatalog);
        if (!$dataStream) {
            $dataStream = $this->dataStreamRepository->createForEntity($metadata, $localizedCatalog);
        }

        return $dataStream;
    }
}
