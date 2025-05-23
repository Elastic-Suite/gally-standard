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
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use Gally\Index\Entity\Index;
use Gally\Index\Repository\Index\IndexRepositoryInterface;
use Gally\Index\Service\IndexOperation;

class InstallIndexMutation implements MutationResolverInterface
{
    public function __construct(
        private IndexOperation $indexOperation,
        private IndexRepositoryInterface $indexRepository
    ) {
    }

    /**
     * @param object|null  $item    The item to be mutated
     * @param array<mixed> $context Context
     */
    public function __invoke(?object $item, array $context): ?object
    {
        /** @var Index $item */
        $index = $this->indexRepository->findByName($item->getName());
        if (null === $index) {
            throw new InvalidArgumentException(\sprintf('Index [%s] does not exist', $item->getName()));
        }

        $this->indexOperation->installIndexByName($index->getName());

        // Reload the index to get updated aliases.
        return $this->indexRepository->findByName($item->getName());
    }
}
