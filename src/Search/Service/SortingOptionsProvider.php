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

namespace Gally\Search\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Gally\Cache\Service\CacheManagerInterface;
use Gally\Catalog\Entity\LocalizedCatalog;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Metadata\Entity\SourceField;
use Gally\Metadata\Repository\SourceFieldLabelRepository;
use Gally\Metadata\Service\MetadataSourceFieldProviderCache;
use Gally\Search\Elasticsearch\Request\SortOrderInterface;
use Gally\Search\GraphQl\Type\Definition\SortOrder\SortOrderProviderInterface as ProductSortOrderProviderInterface;

class SortingOptionsProvider
{
    /** @var array<string, array> */
    private array $sortingOptionsCache = [];

    public function __construct(
        private MetadataSourceFieldProviderCache $metadataSourceFieldProviderCache,
        private iterable $sortOrderProviders,
        private SourceFieldLabelRepository $sourceFieldLabelRepository,
        private LocalizedCatalogRepository $localizedCatalogRepository,
        private CacheManagerInterface $cacheManager,
    ) {
    }

    /**
     * Return all entity sorting options.
     * When $localizedCatalog is provided, labels are resolved for that localized catalog.
     *
     * Level 1 — local PHP array: avoids repeated Redis round-trips within the same request.
     * Level 2 — Redis (via CacheManager): persists computed options across requests.
     */
    public function getAllSortingOptions(string $entityType, ?LocalizedCatalog $localizedCatalog = null): array
    {
        $cacheKey = $entityType . '_' . ($localizedCatalog?->getId() ?? 'default');

        if (!isset($this->sortingOptionsCache[$cacheKey])) {
            $this->sortingOptionsCache[$cacheKey] = $this->cacheManager->get(
                'gally_sorting_options_' . $cacheKey,
                function (&$tags, &$ttl) use ($entityType, $localizedCatalog): array {
                    $sortableSourceFields = $this->metadataSourceFieldProviderCache->getSortableSourceFields($entityType);

                    $resolvedLocalizedCatalog = $localizedCatalog
                        ?? $this->localizedCatalogRepository->findOneBy(['isDefault' => true]);

                    $sourceFieldsById = [];
                    foreach ($sortableSourceFields as $sf) {
                        $sf->setLabels(new ArrayCollection());
                        $sourceFieldsById[$sf->getId()] = $sf;
                    }
                    $sfLabels = $this->sourceFieldLabelRepository->findBy([
                        'localizedCatalog' => $resolvedLocalizedCatalog,
                        'sourceField' => $sortableSourceFields,
                    ]);
                    foreach ($sfLabels as $sfLabel) {
                        $sfId = $sfLabel->getSourceField()->getId();
                        if (isset($sourceFieldsById[$sfId])) {
                            $sourceFieldsById[$sfId]->addLabel($sfLabel);
                        }
                    }

                    $sortOptions = [];
                    foreach ($sortableSourceFields as $sourceField) {
                        // Id source field need to be sortable to be used as default sort option,
                        // but we don't want to have it in the list.
                        if ('id' === $sourceField->getCode()) {
                            continue;
                        }
                        /** @var ProductSortOrderProviderInterface $sortOrderProvider */
                        foreach ($this->sortOrderProviders as $sortOrderProvider) {
                            if ($sortOrderProvider->supports($sourceField)) {
                                $sortOptions[] = [
                                    'code' => $sortOrderProvider->getSortOrderField($sourceField),
                                    'label' => $sortOrderProvider->getSimplifiedLabel($sourceField, $resolvedLocalizedCatalog),
                                    'type' => $sourceField->getType(),
                                ];
                            }
                        }
                    }

                    $sortOptions[] = [
                        'code' => SortOrderInterface::DEFAULT_SORT_FIELD,
                        'label' => 'Relevance',
                        'type' => SourceField\Type::TYPE_FLOAT,
                    ];

                    return $sortOptions;
                },
                [MetadataSourceFieldProviderCache::CACHE_TAG_SOURCE_FIELDS, MetadataSourceFieldProviderCache::getEntityTag($entityType)]
            );
        }

        return $this->sortingOptionsCache[$cacheKey];
    }
}
