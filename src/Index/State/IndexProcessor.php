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

namespace Gally\Index\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Gally\Index\Entity\Index;
use Gally\Index\Repository\Index\IndexRepositoryInterface;

class IndexProcessor implements ProcessorInterface
{
    public function __construct(
        private IndexRepositoryInterface $indexRepository
    ) {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if ($operation instanceof DeleteOperationInterface) {
            /** @var Index $data */
            $this->indexRepository->delete($data->getName());
        } else {
            $this->indexRepository->create($data->getName(), [], $data->getAliases());
        }
    }
}
