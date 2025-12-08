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

namespace Gally\Index\MutationResolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Index\Entity\DataStream;
use Gally\Index\Repository\DataStream\DataStreamRepositoryInterface;
use Gally\Metadata\Repository\MetadataRepository;

class CreateDataStreamMutation implements MutationResolverInterface
{
    public function __construct(
        private LocalizedCatalogRepository $localizedCatalogRepository,
        private MetadataRepository $metadataRepository,
        private DataStreamRepositoryInterface $dataStreamRepository,
    ) {
    }

    public function __invoke($item, array $context): DataStream
    {
        $entityType = $context['args']['input']['entityType'];
        $localizedCatalogId = $context['args']['input']['localizedCatalog'];

        $localizedCatalog = $this->localizedCatalogRepository->findByCodeOrId($localizedCatalogId);
        $metadata = $this->metadataRepository->findByEntity($entityType);

        return $this->dataStreamRepository->createForEntity($metadata, $localizedCatalog);
    }
}
