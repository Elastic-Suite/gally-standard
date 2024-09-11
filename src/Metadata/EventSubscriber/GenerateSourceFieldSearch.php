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

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Gally\Catalog\Entity\LocalizedCatalog;
use Gally\Metadata\Entity\SourceField;
use Gally\Metadata\Entity\SourceFieldLabel;
use Gally\Metadata\Repository\SourceFieldRepository;

class GenerateSourceFieldSearch
{
    public function __construct(
        private SourceFieldRepository $sourceFieldRepository,
    ) {
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $this->generateSearchField($args->getObject());
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $this->generateSearchField($args->getObject());
    }

    private function generateSearchField(object $entity): void
    {
        if ($entity instanceof SourceField) {
            $this->setSourceFieldSearch($entity);
        } elseif ($entity instanceof SourceFieldLabel && $entity->getLocalizedCatalog()->getIsDefault()) {
            $this->setSourceFieldSearch($entity->getSourceField(), $entity);
        }
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $entityManager = $args->getObjectManager();
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
