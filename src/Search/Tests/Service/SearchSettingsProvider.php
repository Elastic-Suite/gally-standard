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

namespace Gally\Search\Tests\Service;

class SearchSettingsProvider extends \Gally\Search\Service\SearchSettingsProvider
{
    private bool $coverageUseIndexedFieldsPropertyValue;

    public function coverageUseIndexedFieldsProperty(): bool
    {
        return $this->coverageUseIndexedFieldsPropertyValue ?? parent::coverageUseIndexedFieldsProperty();
    }

    public function setCoverageUseIndexedFieldsProperty(bool $value): void
    {
        $this->coverageUseIndexedFieldsPropertyValue = $value;
    }
}
