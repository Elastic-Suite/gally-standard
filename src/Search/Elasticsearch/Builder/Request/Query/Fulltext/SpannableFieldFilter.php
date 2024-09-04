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

namespace Gally\Search\Elasticsearch\Builder\Request\Query\Fulltext;

use Gally\Index\Entity\Index\Mapping\FieldFilterInterface;
use Gally\Index\Entity\Index\Mapping\FieldInterface;

/**
 * Indicates if a field can be used in span query.
 */
class SpannableFieldFilter implements FieldFilterInterface
{
    public function filterField(FieldInterface $field): bool
    {
        return $field->isSearchable()
            && false === $field->isNested()
            && $field->isSpannable();
    }
}
