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

use Gally\Metadata\Entity\SourceField;
use Gally\Metadata\Repository\MetadataRepository;
use Gally\Search\Elasticsearch\Request\SortOrderInterface;
use Gally\Search\GraphQl\Type\Definition\SortOrder\SortOrderProviderInterface as ProductSortOrderProviderInterface;

class SortingOptionsProvider
{
    private ?array $sortingOptions;

    public function __construct(
        private MetadataRepository $metadataRepository,
        private iterable $sortOrderProviders
    ) {
        $this->sortingOptions = null;
    }

    /**
     * Return all entity sorting options for categories.
     */
    public function getAllSortingOptions(string $entityType): array
    {
        // Exception thrown if the entity does not exist.
        $metadata = $this->metadataRepository->findByEntity($entityType);

        if (null === $this->sortingOptions) {
            $sortOptions = [];
            foreach ($metadata->getSortableSourceFields() as $sourceField) {
                // Id source field need to be sortable to be used as default sort option,
                // but we don't want to have it in the list
                if ('id' === $sourceField->getCode()) {
                    continue;
                }
                /** @var ProductSortOrderProviderInterface $sortOrderProvider */
                foreach ($this->sortOrderProviders as $sortOrderProvider) {
                    if ($sortOrderProvider->supports($sourceField)) {
                        $sortOptions[] = [
                            'code' => $sortOrderProvider->getSortOrderField($sourceField),
                            'label' => $sortOrderProvider->getSimplifiedLabel($sourceField),
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

            $this->sortingOptions = $sortOptions;
        }

        return $this->sortingOptions;
    }
}
