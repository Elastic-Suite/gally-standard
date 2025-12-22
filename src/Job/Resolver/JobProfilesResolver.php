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

namespace Gally\Job\Resolver;

use Gally\Job\Entity\Job;
use Gally\Job\Service\JobManager;

class JobProfilesResolver
{
    public function __construct(
        private JobManager $jobManager,
    ) {
    }

    public function __invoke($item, array $context): Job\Profiles
    {
        $jobProfiles = new Job\Profiles();

        return $jobProfiles->setProfiles($this->jobManager->getProfiles());
    }
}
