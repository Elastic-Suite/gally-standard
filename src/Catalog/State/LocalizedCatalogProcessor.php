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

namespace Gally\Catalog\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Gally\Catalog\Model\LocalizedCatalog;
use Gally\Catalog\Repository\LocalizedCatalogRepository;

class LocalizedCatalogProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LocalizedCatalogRepository $localizedCatalogRepository,
        private ProcessorInterface $persistProcessor,
        private ProcessorInterface $removeProcessor,
    ) {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): ?LocalizedCatalog
    {
        if ($operation instanceof DeleteOperationInterface) {
            return $this->removeProcessor->process($data, $operation, $uriVariables, $context);
        }

        if ($data->getIsDefault()) {
            // Set it to false  because it will be set to false in the function unsetDefaultLocalizedCatalog
            $data->setIsDefault(false);
            $this->localizedCatalogRepository->unsetDefaultLocalizedCatalog();
            $this->entityManager->flush();

            // Set it back to true to mark it as updated in the entity manager.
            $data->setIsDefault(true);
        }

        $result = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        $this->setOneAsDefault();

        return $result;
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
