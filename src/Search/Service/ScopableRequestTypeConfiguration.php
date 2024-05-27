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

namespace Gally\Search\Service;

use Gally\Catalog\Model\LocalizedCatalog;

abstract class ScopableRequestTypeConfiguration
{
    /**
     * Allows to extract the config when there are localized catalog and request type config levels to determine the scope  of a config.
     * Example:
     * relevance:............................├ Root config node
     *   global:.............................├ Localized catalog code (scope)
     *     request_types:....................├ Request types node
     *       generic:........................├ Request type code (scope)
     *         fulltext:.....................├ Config
     *           minimumShouldMatch: '100%'..├ config
     * In this config file example, the function will return an array from the fulltext node.
     */
    public function getConfig(array $scopedConfig, ?LocalizedCatalog $localizedCatalog, ?string $requestType): array
    {
        $localizedCatalogCode = $localizedCatalog?->getCode() ?? 'global';
        $requestType = $requestType ?? 'generic';

        $defaultConfig = $scopedConfig['global']['request_types']['generic'];
        $defaultLocalizedConfig = $scopedConfig[$localizedCatalogCode]['request_types']['generic'] ?? [];
        $defaultRequestTypeConfig = $scopedConfig['global']['request_types'][$requestType] ?? [];
        $defaultConfig = array_replace_recursive($defaultConfig, $defaultLocalizedConfig, $defaultRequestTypeConfig);

        $config = $scopedConfig[$localizedCatalogCode]['request_types'][$requestType] ?? [];

        return array_replace_recursive($defaultConfig, $config);
    }
}
