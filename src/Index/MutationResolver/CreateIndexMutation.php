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
use Gally\Index\Service\IndexOperation;
use Gally\Metadata\Repository\MetadataRepository;
use Psr\Log\LoggerInterface;

class CreateIndexMutation implements MutationResolverInterface
{
    public function __construct(
        private LocalizedCatalogRepository $localizedCatalogRepository,
        private MetadataRepository $metadataRepository,
        private IndexOperation $indexOperation,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Handle mutation.
     *
     * @param object|null  $item    The item to be mutated
     * @param array<mixed> $context Context
     *
     * @throws \Exception
     *
     * @return object|null The mutated item
     */
    public function __invoke(?object $item, array $context): ?object
    {
        $entityType = $context['args']['input']['entityType'];
        $localizedCatalogCode = $context['args']['input']['localizedCatalog'];
        $metadata = $this->metadataRepository->findByEntity($entityType);
        $catalog = $this->localizedCatalogRepository->findByCodeOrId($localizedCatalogCode);

        try {
            $item = $this->indexOperation->createEntityIndex($metadata, $catalog);
        } catch (\Exception $exception) {
            $this->logger->error($exception);
            throw new \Exception('An error occurred when creating the index: ' . $exception->getMessage());
        }

        return $item;
    }
}
