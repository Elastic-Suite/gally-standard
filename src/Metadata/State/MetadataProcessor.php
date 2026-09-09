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

namespace Gally\Metadata\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Gally\Metadata\Entity\Metadata;

class MetadataProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $persistProcessor,
        private ProcessorInterface $removeProcessor,
    ) {
    }

    /**
     * @param Metadata $data
     */
    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): ?Metadata
    {
        if ($operation instanceof DeleteOperationInterface) {
            if ($data->getIsSystem()) {
                throw new InvalidArgumentException(\sprintf("You can't remove system metadata '%s'", $data->getEntity()));
            }

            return $this->removeProcessor->process($data, $operation, $uriVariables, $context);
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
