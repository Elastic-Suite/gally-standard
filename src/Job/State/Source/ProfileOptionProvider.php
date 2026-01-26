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

namespace Gally\Job\State\Source;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Gally\Job\Service\JobManager;

class ProfileOptionProvider implements ProviderInterface
{
    public function __construct(
        private JobManager $jobManager,
        private ProviderInterface $itemProvider,
    ) {
    }

    /**
     * @return ?array
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if (!$operation instanceof CollectionOperationInterface) {
            return $this->itemProvider->provide($operation, $uriVariables, $context);
        }

        $jobType = $context['args']['jobType'] ?? (isset($context['request']) ? $context['request']->get('jobType') : null);

        return $this->jobManager->getProfileOptions($jobType);
    }
}
