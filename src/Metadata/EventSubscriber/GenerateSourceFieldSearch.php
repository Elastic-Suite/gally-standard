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
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Gally\Catalog\Model\LocalizedCatalog;
use Gally\Metadata\Model\SourceField;
use Gally\Metadata\Model\SourceFieldLabel;
use Gally\Metadata\Repository\SourceFieldRepository;

class GenerateSourceFieldSearch implements EventSubscriberInterface
{
    public function __construct(
        private SourceFieldRepository $sourceFieldRepository,
    ) {
    }

    public function getSubscribedEvents(): array
    {
        return [Events::prePersist, Events::preUpdate, Events::onFlush];
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $this->generateSearchField($args);
    }

    public function preUpdate(LifecycleEventArgs $args): void
    {
        $this->generateSearchField($args);
    }

    private function generateSearchField(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($entity instanceof SourceField) {
            $this->setSourceFieldSearch($entity);
        } elseif ($entity instanceof SourceFieldLabel && $entity->getLocalizedCatalog()->getIsDefault()) {
            $this->setSourceFieldSearch($entity->getSourceField(), $entity);
        }
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $entityManager = $args->getEntityManager();
        $unitOfWork = $entityManager->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityUpdates() as $keyEntity => $entity) {
            if ($entity instanceof LocalizedCatalog) {
                $sourceFields = $this->sourceFieldRepository->findAll();
                foreach ($sourceFields as $sourceField) {
                    $this->setSourceFieldSearch($sourceField, null, $entity);
                    $metaData = $entityManager->getClassMetadata(SourceField::class);
                    $unitOfWork->computeChangeSet($metaData, $sourceField);
                }
            }
        }
    }

    private function setSourceFieldSearch(
        SourceField $sourceField,
        ?SourceFieldLabel $label = null,
        ?LocalizedCatalog $localizedCatalog = null,
    ): void {
        $search = [
            $sourceField->getCode(),
            $label
                ? $label->getLabel()
                : (
                    $localizedCatalog && $localizedCatalog->getIsDefault()
                        ? $sourceField->getLabel($localizedCatalog->getId())
                        : $sourceField->getDefaultLabel()
                ),
        ];
        $sourceField->setSearch(implode(' ', $search));
    }
}
