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

namespace Gally\Index\State;

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\ProviderInterface;
use Gally\Index\Dto\InstallIndexDto;
use Gally\Index\Model\Index;
use Gally\Index\Repository\Index\IndexRepositoryInterface;
use Gally\Index\Service\IndexOperation;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class InstallIndexProcessor implements ProcessorInterface
{
    public function __construct(
        private IndexOperation $indexOperation,
        private IndexRepositoryInterface $indexRepository,
        private SerializerInterface $serializer
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * @param InstallIndexDto $data data
     *
     * @throws InvalidArgumentException
     */
    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $index = $context['previous_data'] ?? null;
        if ($index instanceof Index) {
            $this->indexOperation->installIndexByName($index->getName());

            // Reload the index to get updated aliases.
            $indexReloaded =  $this->indexRepository->findByName($index->getName());

            $request = $context['request'] ??  null;
            $format = $request?->getRequestFormat() ?? 'jsonld';

            return $this->serializer->serialize($indexReloaded, $format);
        }

        return null;
    }
}
