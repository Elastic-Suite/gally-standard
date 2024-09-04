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

namespace Gally\Search\Elasticsearch\Request;

/**
 * Define span query types usable in Gally.
 */
interface SpanQueryInterface extends QueryInterface
{
    public const TYPE_SPAN_NEAR = 'spanNearQuery';
    public const TYPE_SPAN_TERM = 'spanTermQuery';
}
