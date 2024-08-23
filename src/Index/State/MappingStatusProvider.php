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

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use ApiPlatform\State\ProviderInterface;
use Gally\Index\Model\Index\Mapping\Status;
use Gally\Index\Service\MetadataManager;
use Gally\Metadata\Repository\MetadataRepository;

class MappingStatusProvider implements ProviderInterface
{
    public function __construct(
        private MetadataRepository $metadataRepository,
        private MetadataManager $metadataManager
    ) {
    }

    /**
     * @return ?Status
     */
    public function __invoke(mixed $item, array $context): object
    {
        return $this->provide(new Get(), ['id' => $context['args']['entityType']], $context);
    }

    /**
     * @return T|PartialPaginatorInterface<T>|iterable<T>|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $metadata = $this->metadataRepository->findOneBy(['entity' => $uriVariables['id']]);

        return $metadata ? $this->metadataManager->getMappingStatus($metadata) : null;
    }
}
