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

namespace Gally\Job\MessageHandler;

use Gally\Job\Message\ProcessJob;
use Gally\Job\Service\JobManager;

class ProcessJobHandler
{
    public function __construct(private JobManager $jobManager)
    {
    }

    public function __invoke(ProcessJob $message): void
    {
        $this->jobManager->processByJobId($message->getJobId());
    }
}
