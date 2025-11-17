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

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Gally\Index\Dto\RefreshIndexDto;
use Gally\Index\Entity\Index;
use Gally\Index\Repository\Index\IndexRepositoryInterface;
use Symfony\Component\Serializer\SerializerInterface;

class RefreshIndexProcessor implements ProcessorInterface
{
    public function __construct(
        private IndexRepositoryInterface $indexRepository,
        private SerializerInterface $serializer,
    ) {
    }

    /**
     * @param RefreshIndexDto $data data
     *
     * @throws InvalidArgumentException
     */
    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): ?string
    {
        if (($context['previous_data'] ?? null) instanceof Index) {
            $index = $context['previous_data'];
            $this->indexRepository->refresh($index->getName());

            $request = $context['request'] ?? null;
            $format = $request?->getRequestFormat() ?? 'jsonld';

            return $this->serializer->serialize($index, $format);
        }

        return null;
    }
}
