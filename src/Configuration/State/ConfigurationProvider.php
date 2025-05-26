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

namespace Gally\Configuration\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Gally\Configuration\Entity\Configuration;
use Gally\Configuration\Service\ConfigurationManager;

class ConfigurationProvider implements ProviderInterface
{
    public function __construct(
        private ConfigurationManager $configurationManager,
        private ProviderInterface $itemProvider,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Configuration|array|null
    {
        if (!$operation instanceof CollectionOperationInterface) {
            return $this->itemProvider->provide($operation, $uriVariables, $context);
        }

        $path = (!isset($context['filters']['path']) || '' === $context['filters']['path'])
            ? []
            : $context['filters']['path'];
        $currentPage = $context['filters']['currentPage'] ?? null;
        $pageSize = $context['filters']['pageSize'] ?? null;

        $data = $this->configurationManager->getMultiScopedConfigurations(
            $path,
            [
                Configuration::SCOPE_LANGUAGE => $context['filters']['language'] ?? null,
                Configuration::SCOPE_LOCALE => $context['filters']['localeCode'] ?? null,
                Configuration::SCOPE_REQUEST_TYPE => $context['filters']['requestType'] ?? null,
                Configuration::SCOPE_LOCALIZED_CATALOG => $context['filters']['localizedCatalogCode'] ?? null,
            ],
            $context['only_public'] ?? false
        );

        if (null !== $pageSize) {
            $pageSize = (int) $pageSize;
            $currentPage = (int) $currentPage;
            $offset = ($currentPage - 1) * $pageSize;

            $data = \array_slice($data, $offset, $pageSize);
        }

        return $data;
    }
}
