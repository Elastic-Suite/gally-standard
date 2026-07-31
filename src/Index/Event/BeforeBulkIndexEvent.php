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

use Gally\Index\Entity\Index;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched before a bulk of raw documents is written to Elasticsearch/OpenSearch. Listeners
 * may validate the incoming raw data and throw to abort the bulk before anything is written
 * (as opposed to AfterBulkIndexEvent, which fires once the data is already indexed and, when
 * the index is already installed, already live).
 */
class BeforeBulkIndexEvent extends Event
{
    public const NAME = 'gally.index.before_bulk';

    public function __construct(
        private readonly Index $index,
        private readonly array $data = [],
    ) {
    }

    public function getIndex(): Index
    {
        return $this->index;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
