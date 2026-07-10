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
 * Dispatched before an index is switched to its live alias (ie. before it becomes readable
 * through the API). Listeners may validate the index content and throw to abort the install,
 * leaving the previously installed index untouched.
 */
class BeforeInstallIndexEvent extends Event
{
    public const NAME = 'gally.index.before_install';

    public function __construct(private readonly Index $index)
    {
    }

    public function getIndex(): Index
    {
        return $this->index;
    }
}
