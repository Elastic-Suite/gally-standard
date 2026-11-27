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

namespace Gally\Index\Event;

use Gally\Index\Dto\Bulk;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched by the index and data stream repository decorators right before a bulk request is sent.
 * The request may target several indices and data streams and contain delete operations:
 * listeners are expected to filter on the targets they handle and may replace their documents.
 */
class BeforeBulkRequestEvent extends Event
{
    public const NAME = 'gally.index.before_bulk_request';

    public function __construct(private readonly Bulk\Request $request)
    {
    }

    public function getRequest(): Bulk\Request
    {
        return $this->request;
    }
}
