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

use Gally\Search\State\DocumentSourceFieldLabelProvider;

class ProductSourceFieldLabelProvider extends DocumentSourceFieldLabelProvider
{
    protected const ENTITY_TYPE = 'product';

    protected function getEntityType(array $context): string
    {
        return static::ENTITY_TYPE;
    }
}
