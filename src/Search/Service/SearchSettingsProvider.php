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

namespace Gally\Search\Service;

use Gally\Configuration\Service\ConfigurationManager;

class SearchSettingsProvider
{
    public function __construct(private ConfigurationManager $configurationManager)
    {
    }

    /**
     * If the coverageUseIndexedFieldsProperty config is set to false (default value),
     * we will deduce ourselves the indexed fields based on a (potentially) costly aggregation.
     * If this config is set to true, we will use the "index_fields" field to build this aggregation.
     */
    public function coverageUseIndexedFieldsProperty(): bool
    {
        return $this->configurationManager
            ->getScopedConfigValue('gally.search_settings.aggregations.coverage_use_indexed_fields_property');
    }

    /**
     * Get sort field that need to be sort ascending by default.
     */
    public function getDefaultDescSortField(): array
    {
        return $this->configurationManager
            ->getScopedConfigValue('gally.search_settings.sort.default_desc_sort_field');
    }
}
