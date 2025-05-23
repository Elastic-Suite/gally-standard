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

namespace Gally\Product\Decoration\CategoryData;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use Gally\Index\Entity\Index;
use Gally\Product\Service\CategoryNameUpdater;

class SyncCategoryNameAfterBulk implements MutationResolverInterface
{
    public function __construct(
        private MutationResolverInterface $decorated,
        private CategoryNameUpdater $categoryNameUpdater
    ) {
    }

    public function __invoke(?object $item, array $context): ?object
    {
        /** @var Index $index */
        $index = $this->decorated->__invoke($item, $context);

        if (null !== $index->getEntityType()) {
            if ('category' === $index->getEntityType()) {
                // Handle category name change ?
            }

            if (('product' === $index->getEntityType()) && $index->getLocalizedCatalog()) {
                // Handle copying category.name to category._name
                $this->categoryNameUpdater->updateCategoryNames(
                    $index,
                    json_decode($context['args']['input']['data'], true)
                );
            }
        }

        return $index;
    }
}
