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

class AfterInstallIndexEvent extends Event
{
    public const NAME = 'gally.index.after_install';

    public function __construct(private readonly Index $index)
    {
    }

    public function getIndex(): Index
    {
        return $this->index;
    }
}
