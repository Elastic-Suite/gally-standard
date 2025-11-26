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

namespace Gally\Tracker\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\Post;
use Gally\Tracker\State\TrackingEventProcessor;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new Post(
            processor: TrackingEventProcessor::class,
            output: false
        ),
    ],
    graphQlOperations: [
        new Mutation(
            name: 'create',
            processor: TrackingEventProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['tracking_event:read']],
    denormalizationContext: ['groups' => ['tracking_event:write']]
)]
class TrackingEvent
{
    #[ApiProperty(identifier: true)]
    #[Groups(['tracking_event:read'])]
    private ?string $id = null;

    #[Groups(['tracking_event:read', 'tracking_event:write'])]
    private string $eventType;

    #[Groups(['tracking_event:read', 'tracking_event:write'])]
    private string $metadataCode;

    #[Groups(['tracking_event:read', 'tracking_event:write'])]
    private string $localizedCatalogCode;

    #[Groups(['tracking_event:read', 'tracking_event:write'])]
    private ?string $entityCode = null;

    #[Groups(['tracking_event:read', 'tracking_event:write'])]
    private ?string $source = null;

    #[Groups(['tracking_event:read', 'tracking_event:write'])]
    private ?string $context = null;

    #[Groups(['tracking_event:read', 'tracking_event:write'])]
    private ?string $sessionUid = null;

    #[Groups(['tracking_event:read', 'tracking_event:write'])]
    private ?string $sessionVid = null;

    #[Groups(['tracking_event:read', 'tracking_event:write'])]
    private ?string $groupId = '0';

    #[Groups(['tracking_event:read', 'tracking_event:write'])]
    private ?string $payload = null;

    #[Groups(['tracking_event:read', 'tracking_event:write'])]
    private ?string $items = null;

    #[Groups(['tracking_event:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        if (null === $this->id) {
            $this->id = md5(json_encode($this->getData()) . time());
        }

        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function setEventType(string $eventType): self
    {
        $this->eventType = $eventType;

        return $this;
    }

    public function getMetadataCode(): string
    {
        return $this->metadataCode;
    }

    public function setMetadataCode(string $metadataCode): self
    {
        $this->metadataCode = $metadataCode;

        return $this;
    }

    public function getLocalizedCatalogCode(): string
    {
        return $this->localizedCatalogCode;
    }

    public function setLocalizedCatalogCode(string $localizedCatalogCode): self
    {
        $this->localizedCatalogCode = $localizedCatalogCode;

        return $this;
    }

    public function getEntityCode(): ?string
    {
        return $this->entityCode;
    }

    public function setEntityCode(?string $entityCode): self
    {
        $this->entityCode = $entityCode;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): self
    {
        $this->source = $source;

        return $this;
    }

    public function getContext(): ?string
    {
        return $this->context;
    }

    public function setContext(?string $context): self
    {
        $this->context = $context;

        return $this;
    }

    public function getSessionUid(): ?string
    {
        return $this->sessionUid;
    }

    public function setSessionUid(?string $sessionUid): self
    {
        $this->sessionUid = $sessionUid;

        return $this;
    }

    public function getSessionVid(): ?string
    {
        return $this->sessionVid;
    }

    public function setSessionVid(?string $sessionVid): self
    {
        $this->sessionVid = $sessionVid;

        return $this;
    }

    public function getGroupId(): ?string
    {
        return $this->groupId;
    }

    public function setGroupId(?string $groupId): self
    {
        $this->groupId = $groupId;

        return $this;
    }

    public function getPayload(): ?string
    {
        return $this->payload;
    }

    public function setPayload(?string $payload): self
    {
        $this->payload = $payload;

        return $this;
    }

    public function getItems(): string
    {
        return $this->items ?? '[]';
    }

    public function setItems(?string $items): self
    {
        $this->items = $items;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function toArray(): array
    {
        return array_merge(
            $this->getData(),
            ['event' => ['id' => $this->getId()]],
        );
    }

    protected function getData(): array
    {
        $payloadData = [];
        if ($this->payload) {
            $payloadData = json_decode($this->payload, true) ?? [];
        }

        return array_merge(
            [
                '@timestamp' => $this->createdAt->format('c'),
                'event_type' => $this->getEventType(),
                'metadata_code' => $this->getMetadataCode(),
                'localized_catalog_code' => $this->getLocalizedCatalogCode(),
                'entity_code' => $this->getEntityCode(),
                'source' => $this->getSource(),
                'context' => $this->getContext(),
                'session' => [
                    'uid' => $this->getSessionUid(),
                    'vid' => $this->getSessionVid(),
                ],
                'group_id' => $this->getGroupId(),
                'items' => $this->getItems(),
            ],
            $payloadData
        );
    }
}
