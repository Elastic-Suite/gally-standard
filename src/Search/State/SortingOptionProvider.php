<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @package   Gally
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Gally\Search\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use ApiPlatform\State\ProviderInterface;
use Gally\Search\Model\Source\SortingOption;
use Gally\Search\Service\SortingOptionsProvider;

class SortingOptionProvider implements ProviderInterface
{
    public function __construct(
        private SortingOptionsProvider $sortingOptionsProvider,
        protected ProviderInterface $provider,
    ) {
    }

    /**
     * @return PartialPaginatorInterface<SortingOption>|iterable<SortingOption>|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if (!$operation instanceof CollectionOperationInterface) {
            return $this->provider->provide($operation, $uriVariables, $context);
        }

        $entityType = $context['filters']['entityType'] ?? null;

        return $this->sortingOptionsProvider->getAllSortingOptions($entityType);
    }
}
