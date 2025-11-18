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

namespace Gally\Product\State\Facet;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use Gally\Search\Entity\Facet\Option;

class OptionProvider extends \Gally\Search\State\Facet\OptionProvider
{
    /**
     * @return PartialPaginatorInterface<Option>|iterable<Option>|Option|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if (!$operation instanceof CollectionOperationInterface) {
            return $this->itemProvider->provide($operation, $uriVariables, $context);
        }

        $context['filters']['entityType'] = 'product';
        $currentCategoryId = $context['filters']['currentCategoryId'] ?? null;
        if ($currentCategoryId) {
            $this->currentCategoryProvider->setCurrentCategory($currentCategoryId);
        }

        return parent::provide($operation, $uriVariables, $context);
    }
}
