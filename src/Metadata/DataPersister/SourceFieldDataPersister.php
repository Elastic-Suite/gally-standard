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

namespace Gally\Metadata\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Gally\Metadata\Model\SourceField;
use Gally\Metadata\Model\SourceFieldLabel;

class SourceFieldDataPersister implements DataPersisterInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function supports($data): bool
    {
        return $data instanceof SourceField;
    }

    /**
     * {@inheritdoc}
     *
     * @param SourceField $data
     *
     * @throws Exception
     *
     * @return SourceField
     */
    public function persist($data)
    {
        $sourceField = $data;

        try {
            $this->entityManager->beginTransaction();

            // Is it an update ?
            if ($this->entityManager->getUnitOfWork()->isInIdentityMap($sourceField)) {
                // Call function computeChangeSets to get the entity changes from the function getEntityChangeSet.
                $this->entityManager->getUnitOfWork()->computeChangeSets();
                $changeSet = $this->entityManager->getUnitOfWork()->getEntityChangeSet($sourceField);

                unset($changeSet['isSpellchecked']);
                unset($changeSet['weight']);

                // Prevent user to update a system source field, only the value of 'weight' and 'isSpellchecked' can be changed.
                if (\count($changeSet) > 0 && ($sourceField->getIsSystem() || ($changeSet['isSystem'][0] ?? false) === true)) {
                    throw new InvalidArgumentException(sprintf("The source field '%s' cannot be updated because it is a system source field, only the value  of 'weight' and 'isSpellchecked' can be changed.", $sourceField->getCode()));
                }

                // Clean entity manager before manage label data.
                $this->entityManager->flush();
            }
            $this->replaceLabels($sourceField);

            $this->entityManager->persist($sourceField);
            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            throw $e;
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     *
     * @param SourceField $data
     */
    public function remove($data)
    {
        // Prevent user to delete system source fields
        if ($data->getIsSystem()) {
            throw new InvalidArgumentException('You can`t remove system source field');
        }

        $this->entityManager->remove($data);
        $this->entityManager->flush();
    }

    /**
     * Remove and re-add sub-resources when it's necessary.
     *
     * To avoid 'Unique violation' error  from database on labels,
     * we have to delete all the label rows related to $sourceField and add them again.
     *
     * For example:
     * If we have these rows in source_field_label table: [id => 1, source_field_id => 1, localized_catalog_id => '1', 'label' => 'Name']
     * and we try to update them via a PUT endpoint by these data : [id => 1, source_field_id => 1, localized_catalog_id => '1', 'label' => 'Names']
     * during the save process API Platform will run an update query for the first row, but it will raise a 'Unique violation' error because there is already a label row (id => 1) with the localized catalog 1 related to $sourceField.
     * To avoid this error we remove the labels related to $sourceField and we add them again if it's necessary.
     */
    protected function replaceLabels(SourceField $sourceField): void
    {
        // Save labels in $newLabels.
        $newLabels = [];
        foreach ($sourceField->getLabels() as $label) {
            if ($this->entityManager->getUnitOfWork()->isInIdentityMap($label)) {
                $newLabel = new SourceFieldLabel();
                $newLabel->setSourceField($label->getSourceField());
                $newLabel->setLocalizedCatalog($label->getLocalizedCatalog());
                $newLabel->setLabel($label->getLabel());
                $newLabels[] = $newLabel;
            } else {
                $newLabels[] = $label;
            }
            $this->entityManager->remove($label);
        }

        // Force remove old labels before persist new ones.
        $this->entityManager->flush();

        $sourceField->setLabels(new ArrayCollection());
        foreach ($newLabels as $newLabel) {
            $sourceField->addLabel($newLabel);
        }
    }
}
