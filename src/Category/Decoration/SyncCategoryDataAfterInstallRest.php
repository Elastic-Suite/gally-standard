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

namespace Gally\Category\Decoration;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Gally\Category\Exception\SyncCategoryException;
use Gally\Category\Service\CategoryProductPositionManager;
use Gally\Category\Service\CategorySynchronizer;
use Gally\Index\Dto\InstallIndexDto;
use Gally\Index\Entity\Index;
use Gally\Index\State\InstallIndexProcessor;
use Symfony\Component\Serializer\SerializerInterface;

class SyncCategoryDataAfterInstallRest implements ProcessorInterface
{
    public function __construct(
        private InstallIndexProcessor $decorated,
        private CategorySynchronizer $synchronizer,
        private CategoryProductPositionManager $categoryProductPositionManager,
        private SerializerInterface $serializer,
    ) {
    }

    /**
     * @param InstallIndexDto $data data
     *
     * @throws InvalidArgumentException
     */
    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): ?string
    {
        /** @var ?string $indexSerialized */
        $indexSerialized = $this->decorated->process($data, $operation, $uriVariables, $context);

        $request = $context['request'] ?? null;
        $format = $request?->getRequestFormat() ?? 'jsonld';
        $index = $this->serializer->deserialize($indexSerialized, Index::class, $format);

        if (!$index instanceof Index) {
            return null;
        }

        if ('category' === $index->getEntityType()) { // Synchronize sql data for category entity
            try {
                $this->synchronizer->synchronize($index);
            } catch (SyncCategoryException) {
                // If sync failed, retry sync once, then log the error.
                $this->synchronizer->synchronize($index);
            }
        }

        if ('product' === $index->getEntityType()) {
            $this->categoryProductPositionManager->reindexPositionsByIndex($index);
        }

        return $this->serializer->serialize($index, $format);
    }
}
