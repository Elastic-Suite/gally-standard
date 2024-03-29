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

namespace Gally\Catalog\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use Doctrine\ORM\EntityManagerInterface;
use Gally\Catalog\Model\LocalizedCatalog;
use Gally\Catalog\Repository\LocalizedCatalogRepository;

class LocalizedCatalogDataPersister implements DataPersisterInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LocalizedCatalogRepository $localizedCatalogRepository
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function supports($data): bool
    {
        return $data instanceof LocalizedCatalog;
    }

    /**
     * {@inheritdoc}
     *
     * @param LocalizedCatalog $data
     *
     * @return LocalizedCatalog
     */
    public function persist($data)
    {
        if ($data->getIsDefault()) {
            // Set it to false  because it will be set to false in the function unsetDefaultLocalizedCatalog
            $data->setIsDefault(false);
            $this->localizedCatalogRepository->unsetDefaultLocalizedCatalog();
            $this->entityManager->flush();

            // Set it back to true to mark it as updated in the entity manager.
            $data->setIsDefault(true);
        }

        $this->entityManager->persist($data);
        $this->entityManager->flush();

        $this->setOneAsDefault();

        return $data;
    }

    /**
     * {@inheritdoc}
     *
     * @param LocalizedCatalog $data
     */
    public function remove($data)
    {
        $this->entityManager->remove($data);
        $this->entityManager->flush();

        $this->setOneAsDefault();
    }

    private function setOneAsDefault(): void
    {
        $defaultLocalizedCatalog = $this->localizedCatalogRepository->findOneBy(['isDefault' => true]);
        if (!$defaultLocalizedCatalog) {
            $defaultLocalizedCatalog = $this->localizedCatalogRepository->findOneBy([], ['id' => 'ASC']);
            $defaultLocalizedCatalog->setIsDefault(true);
            $this->entityManager->persist($defaultLocalizedCatalog);
            $this->entityManager->flush();
        }
    }
}
