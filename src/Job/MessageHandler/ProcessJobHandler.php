<?php
/**
 * DISCLAIMER.
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Licensed to Smile-SA. All rights reserved. No warranty, explicit or implicit, provided.
 *            Unauthorized copying of this file, via any medium, is strictly prohibited.
 */

declare(strict_types=1);

namespace Gally\Job\MessageHandler;


use Gally\Job\Message\ProcessJob;

class ProcessJobHandler
{
    public function __invoke(ProcessJob $message): void
    {
        error_log('ProcessJobHandler - ' . $message->getJoId() . PHP_EOL, 3, '/tmp/botis.log');
    }
}
