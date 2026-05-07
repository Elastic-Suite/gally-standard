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

namespace Gally\Metadata\EventSubscriber;

use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Gally\Metadata\Entity\Metadata;
use Gally\Metadata\Entity\SourceField;
use Gally\Metadata\Entity\SourceFieldLabel;
use Gally\Metadata\Service\MetadataSourceFieldProviderCache;

class InvalidateMetadataSourceFieldCache
{
    public function __construct(
        private MetadataSourceFieldProviderCache $metadataSourceFieldProviderCache,
    ) {
    }

    /**
     * Invalidates cache when a new SourceField or SourceFieldLabel is created.
     */
    public function prePersist(PrePersistEventArgs $args): void
    {
        $this->invalidateFromEntity($args->getObject());
    }

    /**
     * Invalidates cache when a SourceField or SourceFieldLabel is updated.
     */
    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->invalidateFromEntity($args->getObject());
    }

    /**
     * Invalidates cache when a SourceField, SourceFieldLabel or a Metadata is removed.
     */
    public function postRemove(PostRemoveEventArgs $args): void
    {
        $entity = $args->getObject();

        $this->invalidateFromEntity($entity);

        if ($entity instanceof Metadata) {
            $this->metadataSourceFieldProviderCache->invalidate($entity);
        }
    }

    private function invalidateFromEntity(object $entity): void
    {
        $metadata = match (true) {
            $entity instanceof SourceField => $entity->getMetadata(),
            $entity instanceof SourceFieldLabel => $entity->getSourceField()?->getMetadata(),
            default => null,
        };

        if (null !== $metadata) {
            $this->metadataSourceFieldProviderCache->invalidate($metadata);
        }
    }
}
