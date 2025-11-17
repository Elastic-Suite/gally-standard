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

namespace Gally\Search\State\Facet;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Gally\Search\Entity\Facet;

final class ConfigurationProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $persistProcessor,
        private ProcessorInterface $removeProcessor,
    ) {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($operation instanceof DeleteOperationInterface) {
            return $this->removeProcessor->process($data, $operation, $uriVariables, $context);
        }

        if ($data->getDisplayMode() == $data->getDefaultDisplayMode()) {
            $data->setDisplayMode(null);
        }
        if ($data->getCoverageRate() == $data->getDefaultCoverageRate()) {
            $data->setCoverageRate(null);
        }
        if ($data->getMaxSize() == $data->getDefaultMaxSize()) {
            $data->setMaxSize(null);
        }
        if ($data->getSortOrder() == $data->getDefaultSortOrder()) {
            $data->setSortOrder(null);
        }
        if ($data->getIsRecommendable() == $data->getDefaultIsRecommendable()) {
            $data->setIsRecommendable(null);
        }
        if ($data->getIsVirtual() == $data->getDefaultIsVirtual()) {
            $data->setIsVirtual(null);
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }

    public function supports($data): bool
    {
        return $data instanceof Facet\Configuration;
    }
}
