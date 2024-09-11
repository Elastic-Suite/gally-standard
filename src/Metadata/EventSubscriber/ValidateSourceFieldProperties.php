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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Gally\Metadata\Entity\SourceField;

class ValidateSourceFieldProperties
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $this->validateProperties($args->getObject());
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $this->validateProperties($args->getObject());
    }

    private function validateProperties(object $entity): void
    {
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
