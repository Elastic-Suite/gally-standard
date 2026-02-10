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

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Gally\Index\Repository\DataStream\DataStreamRepositoryInterface;

class DataStreamProvider implements ProviderInterface
{
    public function __construct(
        private DataStreamRepositoryInterface $dataStreamRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if (isset($uriVariables['name'])) {
            return $this->dataStreamRepository->findById($uriVariables['name']);
        }

        return $this->dataStreamRepository->findAll();
    }
}
