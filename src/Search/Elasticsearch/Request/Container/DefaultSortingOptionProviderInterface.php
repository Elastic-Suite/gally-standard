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

namespace Gally\Search\Elasticsearch\Request\Container;

use Gally\Search\Elasticsearch\Request\ContainerConfigurationInterface;

/**
 * Default sorting option provider interface for search requests.
 */
interface DefaultSortingOptionProviderInterface
{
    /**
     * Returns aggregations configured in the search container, and according to currently applied query and filters.
     *
     * @param ContainerConfigurationInterface $containerConfig search container configuration
     */
    public function getSortingOption(ContainerConfigurationInterface $containerConfig): array;
}
