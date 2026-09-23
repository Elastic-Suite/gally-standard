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

namespace Gally\Product\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Metadata\Service\SourceFieldLabelResolver;

class ProductSourceFieldLabelProvider implements ProviderInterface
{
    private const ENTITY_TYPE = 'product';

    public function __construct(
        private SourceFieldLabelResolver $sourceFieldLabelResolver,
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
        return $this->sourceFieldLabelResolver->getLabels(
            self::ENTITY_TYPE,
            $this->localizedCatalogRepository->findByCodeOrId((string) $localizedCatalogCode),
            $codes
        );
    }
}
