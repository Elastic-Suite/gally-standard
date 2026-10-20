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

namespace Gally\Search\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Metadata\Service\SourceFieldLabelProvider;

class DocumentSourceFieldLabelProvider implements ProviderInterface
{
    public function __construct(
        private SourceFieldLabelProvider $sourceFieldLabelProvider,
        private LocalizedCatalogRepository $localizedCatalogRepository,
    ) {
    }

    /**
     * @return array<int, array{code: string, label: string}>|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if (!$operation instanceof CollectionOperationInterface) {
            return null;
        }

        $entityType = $this->getEntityType($context);

        $codes = $context['filters']['codes'] ?? $context['args']['codes'] ?? null;
        // A REST caller can send anything, including codes[][]=x, so keep only the scalar entries.
        $codes = \is_array($codes) ? array_map('strval', array_filter($codes, 'is_scalar')) : [];
        if (empty($codes)) {
            throw new InvalidArgumentException('The codes argument is required and cannot be empty.');
        }

        $localizedCatalogCode = $context['filters']['localizedCatalog'] ?? $context['args']['localizedCatalog'] ?? null;
        if (!\is_scalar($localizedCatalogCode) || '' === (string) $localizedCatalogCode) {
            throw new InvalidArgumentException('The localizedCatalog argument is required.');
        }

        // There is deliberately no collection without codes: the source field list must not be discoverable.
        // An unknown entity type is rejected by MetadataRepository::findByEntity().
        return $this->sourceFieldLabelProvider->getLabels(
            $entityType,
            $this->localizedCatalogRepository->findByCodeOrId((string) $localizedCatalogCode),
            $codes
        );
    }

    protected function getEntityType(array $context): string
    {
        $entityType = $context['filters']['entityType'] ?? $context['args']['entityType'] ?? null;
        if (!\is_scalar($entityType) || '' === (string) $entityType) {
            throw new InvalidArgumentException('The entityType argument is required.');
        }

        return (string) $entityType;
    }
}
