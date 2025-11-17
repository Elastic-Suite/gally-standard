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

namespace Gally\Cache\Service;

use ApiPlatform\Serializer\TagCollectorInterface;
use Gally\Configuration\Service\ConfigurationManager;

class TagCollector implements TagCollectorInterface
{
    public function __construct(
        private ConfigurationManager $configManager,
    ) {
    }

    /**
     * Remove related entities cache tags for certain normalization contexts to avoid generating
     * excessively large HTTP headers that could crash the web server.
     *
     * @param array<string, mixed> $context
     */
    public function collect(array $context = []): void
    {
        $iri = $context['iri'] ?? null;
        if (!$iri) {
            return;
        }

        $contextGroups = $context['groups'] ?? [];
        $contextResource = $context['resource_class'] ?? null;

        $cacheTagFilter = $this->configManager->getScopedConfigValue('gally.cache_tag_filter');

        foreach ($cacheTagFilter as $group => $excludedClasses) {
            if (\in_array($group, $contextGroups, true) && \in_array($contextResource, $excludedClasses, true)) {
                return;
            }
        }

        $context['resources'][$iri] = $iri;
    }
}
