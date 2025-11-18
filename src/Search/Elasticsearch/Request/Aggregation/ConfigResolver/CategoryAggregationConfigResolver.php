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

namespace Gally\Search\Elasticsearch\Request\Aggregation\ConfigResolver;

use Gally\Category\Repository\CategoryRepository;
use Gally\Metadata\Entity\SourceField;
use Gally\Search\Elasticsearch\Request\BucketInterface;
use Gally\Search\Elasticsearch\Request\ContainerConfigurationInterface;
use Gally\Search\Elasticsearch\Request\QueryFactory;
use Gally\Search\Elasticsearch\Request\QueryInterface;
use Gally\Search\Service\SearchContext;

class CategoryAggregationConfigResolver implements FieldAggregationConfigResolverInterface
{
    public function __construct(
        private SearchContext $searchContext,
        private CategoryRepository $categoryRepository,
        private QueryFactory $queryFactory,
    ) {
    }

    public function supports(SourceField $sourceField): bool
    {
        return SourceField\Type::TYPE_CATEGORY === $sourceField->getType();
    }

    /**
     * The category aggregation should return only the categories that are direct child of the current category.
     * If no category are provided in context, the aggregation should return only first level categories.
     */
    public function getConfig(ContainerConfigurationInterface $containerConfig, SourceField $sourceField): array
    {
        $config = [];

        $currentCategory = $this->searchContext->getCategory();
        $children = $this->categoryRepository->findBy(['parentId' => $currentCategory]);
        $queries = [];

        foreach ($children as $child) {
            $queries[$child->getId()] = $this->queryFactory->create(
                QueryInterface::TYPE_TERM,
                ['field' => $sourceField->getCode() . '.id', 'value' => $child->getId()]
            );
        }

        if (!empty($queries)) {
            $config = [
                'name' => $sourceField->getCode() . '.id',
                'type' => BucketInterface::TYPE_QUERY_GROUP,
                'queries' => $queries,
            ];
        }

        return $config;
    }
}
