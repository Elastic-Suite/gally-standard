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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Gally\Metadata\Model\SourceField;
use Gally\Metadata\Model\SourceFieldOption;
use Gally\Metadata\Model\SourceFieldOptionLabel;

class SourceFieldOptionDataPersister implements DataPersisterInterface
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
        return $data instanceof SourceFieldOption;
    }

    /**
     * {@inheritdoc}
     *
     * @param SourceFieldOption $data
     *
     * @throws Exception
     *
     * @return SourceFieldOption
     */
    public function persist($data)
    {
        $sourceFieldOption = $data;

        try {
            $this->entityManager->beginTransaction();

            $this->replaceLabels($sourceFieldOption);

            $this->entityManager->persist($sourceFieldOption);
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
     * If we have these rows in source_field_label table: [id => 1, source_field__option_id => 1, localized_catalog_id => '1', 'label' => 'Name']
     * and we try to update them via a PUT endpoint by these data : [id => 1, source_field__option_id => 1, localized_catalog_id => '1', 'label' => 'Names']
     * during the save process API Platform will run an update query for the first row, but it will raise a 'Unique violation' error because there is already a label option row (id => 1) with the localized catalog 1 related to $sourceField.
     * To avoid this error we remove the labels related to $sourceField and we add them again if it's necessary.
     */
    protected function replaceLabels(SourceFieldOption $sourceFieldOption): void
    {
        // Save labels in $newLabels.
        $newLabels = [];
        foreach ($sourceFieldOption->getLabels() as $label) {
            if ($this->entityManager->getUnitOfWork()->isInIdentityMap($label)) {
                $newLabel = new SourceFieldOptionLabel();
                $newLabel->setSourceFieldOption($label->getSourceFieldOption());
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

        $sourceFieldOption->setLabels(new ArrayCollection());
        foreach ($newLabels as $newLabel) {
            $sourceFieldOption->addLabel($newLabel);
        }
    }
}
