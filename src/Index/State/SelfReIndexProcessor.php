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
use ApiPlatform\State\ProcessorInterface;
use Gally\Index\Dto\SelfReindexDto;
use Gally\Index\Model\Index\SelfReindex;
use Gally\Index\Service\SelfReindexOperation;

class SelfReIndexProcessor implements ProcessorInterface
{
    public function __construct(
        private SelfReindexOperation $reindexOperation
    ) {
    }

    /**
     * @param SelfReindexDto $data data
     *
     * @throws \Exception
     */
    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): SelfReindex
    {
        $entityType = $data->entityType;

        return $this->reindexOperation->performReindex($entityType);
    }
}
