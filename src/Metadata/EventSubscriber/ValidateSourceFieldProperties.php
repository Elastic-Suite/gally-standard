<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @package   Gally
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Gally\Metadata\EventSubscriber;

use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Gally\Metadata\Model\SourceField;

class ValidateSourceFieldProperties implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function getSubscribedEvents(): array
    {
        return [Events::prePersist, Events::preUpdate];
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $this->validateProperties($args);
    }

    public function preUpdate(LifecycleEventArgs $args): void
    {
        $this->validateProperties($args);
    }

    private function validateProperties(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof SourceField) {
            return;
        }

        $changeSet = $this->entityManager->getUnitOfWork()->getEntityChangeSet($entity);

        if (\array_key_exists('isFilterable', $changeSet) && !$entity->getIsFilterable()) {
            $entity->setIsUsedInAutocomplete(false);
        }
        if ($entity->getIsUsedInAutocomplete()) {
            $entity->setIsFilterable(true);
        }
    }
}
