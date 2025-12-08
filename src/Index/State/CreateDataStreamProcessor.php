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

namespace Gally\Index\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Index\Dto\CreateDataStreamDto;
use Gally\Index\Entity\DataStream;
use Gally\Index\Repository\DataStream\DataStreamRepositoryInterface;
use Gally\Metadata\Repository\MetadataRepository;
use Psr\Log\LoggerInterface;

class CreateDataStreamProcessor implements ProcessorInterface
{
    public function __construct(
        private LocalizedCatalogRepository $localizedCatalogRepository,
        private MetadataRepository $metadataRepository,
        private DataStreamRepositoryInterface $dataStreamRepository,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param CreateDataStreamDto $data
     */
    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): ?DataStream
    {
        $localizedCatalog = $this->localizedCatalogRepository->findByCodeOrId($data->localizedCatalog);
        $metadata = $this->metadataRepository->findByEntity($data->entityType);

        return $this->dataStreamRepository->createForEntity($metadata, $localizedCatalog);
    }
}
