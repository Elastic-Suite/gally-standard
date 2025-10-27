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
use Gally\Catalog\Entity\LocalizedCatalog;
use Gally\Metadata\Entity\SourceFieldLabel;
use Gally\Metadata\Entity\SourceFieldOptionLabel;

class TagCollector implements TagCollectorInterface
{
    /**
     * Remove related entities' cache tags for certain normalization contexts to avoid generating
     * excessively large HTTP headers that could crash the web server.
     *
     * @param array $context
     * @return void
     */
    public function collect(array $context = []): void
    {
        $iri = $context['iri'] ?? null;
        if (!$iri) {
            return;
        }

        $resource = $context['resource_class'] ?? null;
        $groups = $context['groups'] ?? [];

        $exclusions = [
            'source_field:read' => [
                SourceFieldLabel::class,
                LocalizedCatalog::class,
            ],
            'source_field_option:read' => [
                SourceFieldOptionLabel::class,
                LocalizedCatalog::class,
            ],
        ];

        foreach ($exclusions as $group => $excludedClasses) {
            if (\in_array($group, $groups, true) && \in_array($resource, $excludedClasses, true)) {
                return;
            }
        }

        $context['resources'][$iri] = $iri;
    }
}
