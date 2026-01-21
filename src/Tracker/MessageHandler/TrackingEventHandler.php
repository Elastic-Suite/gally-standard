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

use ApiPlatform\Validator\ValidatorInterface;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Index\Dto\Bulk;
use Gally\Index\Repository\DataStream\DataStreamRepositoryInterface;
use Gally\Metadata\Repository\MetadataRepository;
use Gally\Tracker\Entity\TrackingEvent;
use Symfony\Component\Messenger\Handler\Acknowledger;
use Symfony\Component\Messenger\Handler\BatchHandlerInterface;
use Symfony\Component\Messenger\Handler\BatchHandlerTrait;

class TrackingEventHandler implements BatchHandlerInterface
{
    use BatchHandlerTrait;

    private const BATCH_SIZE = 1000;

    public function __construct(
        private DataStreamRepositoryInterface $dataStreamRepository,
        private MetadataRepository $metadataRepository,
        private LocalizedCatalogRepository $localizedCatalogRepository,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(TrackingEvent $event, ?Acknowledger $ack = null): mixed
    {
        $this->validator->validate($event);
        $this->handle($event, $ack);

        return \count($this->jobs);
    }

    protected function shouldFlush(): bool
    {
        return self::BATCH_SIZE <= \count($this->jobs);
    }

    protected function process(array $jobs): void
    {
        $originalMessageAckMap = [];
        $bulkRequest = new Bulk\Request();
        $metadata = $this->metadataRepository->findOneBy(['entity' => 'tracking_event']);
        $dataStreamMapping = [];

        /** @var TrackingEvent $message */
        foreach ($jobs as [$message, $ack]) {
            $originalMessageAckMap[$message->getId()] = $ack;
            if (!\array_key_exists($message->getLocalizedCatalogCode(), $dataStreamMapping)) {
                $localizedCatalog = $this->localizedCatalogRepository->findByCodeOrId($message->getLocalizedCatalogCode());
                $dataStream = $this->dataStreamRepository->findByMetadata($metadata, $localizedCatalog);
                if (!$dataStream) {
                    $dataStream = $this->dataStreamRepository->createForEntity($metadata, $localizedCatalog);
                }
                $dataStreamMapping[$message->getLocalizedCatalogCode()] = $dataStream;
            }
            $dataStream = $dataStreamMapping[$message->getLocalizedCatalogCode()];
            $bulkRequest->addDocument($dataStream, $message->getId(), $message->toArray());
        }

        $response = $this->dataStreamRepository->bulk($bulkRequest);

        if ($response->hasErrors()) {
            // Track which messages failed and nack them
            foreach ($response['items'] as $item) {
                $docId = $item['index']['_id'];

                if (isset($item['index']['error'])) {
                    if (isset($originalMessageAckMap[$docId])) {
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
}
