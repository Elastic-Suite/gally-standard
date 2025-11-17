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

namespace Gally\Search\Constant\RequestType;

class LimitationType
{
    public const TYPES = [
        self::LIMITATION_CATEGORY => ['label' => self::LABEL_CATEGORY, 'label_all' => self::LABEL_ALL_CATEGORY],
        self::LIMITATION_SEARCH => ['label' => self::LABEL_SEARCH, 'label_all' => self::LABEL_ALL_SEARCH],
    ];
    public const LIMITATION_CATEGORY = 'category';
    public const LIMITATION_SEARCH = 'search';

    public const LABEL_CATEGORY = 'Category';
    public const LABEL_SEARCH = 'Search';

    public const LABEL_ALL_CATEGORY = 'All categories';
    public const LABEL_ALL_SEARCH = 'All search terms';
}
