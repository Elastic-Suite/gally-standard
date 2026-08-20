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

namespace Gally\Fixture\Service;

use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Index\Api\IndexSettingsInterface;
use Gally\Index\Repository\Transform\TransformRepositoryInterface;

class EntityTransformsFixtures implements EntityTransformsFixturesInterface
{
    use GetLocalizedCatalogsTrait;

    public function __construct(
        private LocalizedCatalogRepository $localizedCatalogRepository,
        private IndexSettingsInterface $indexSettings,
        private TransformRepositoryInterface $transformRepository,
    ) {
    }

    public function deleteEntityElasticsearchTransforms(string $entityType, int|string|null $localizedCatalogIdentifier = null): void
    {
        $targetAliases = array_map(
            fn ($localizedCatalog) => $this->indexSettings->getIndexAliasFromIdentifier($entityType, $localizedCatalog),
            $this->getLocalizedCatalogs($localizedCatalogIdentifier)
        );

        foreach ($this->transformRepository->findAll() as $transform) {
            $matchesTargetAlias = array_filter(
                $targetAliases,
                fn (string $targetAlias) => str_starts_with($transform->getTargetIndex(), $targetAlias)
            );

            if (!empty($matchesTargetAlias)) {
                try {
                    $this->transformRepository->stop($transform->getId());
                } catch (\Exception) {
                    // Already stopped.
                }
                $this->transformRepository->delete($transform->getId());
            }
        }
    }
}
