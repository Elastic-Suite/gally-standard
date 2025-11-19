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
use Gally\Job\Entity\Job;
use Symfony\Contracts\Translation\TranslatorInterface;

class StatusOptionProvider implements ProviderInterface
{
    public function __construct(
        private TranslatorInterface $translator,
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

        $statuses = [];
        foreach (Job::STATUS_OPTIONS as $status) {
            $statuses[] = [
                'id' => $status['value'],
                'value' => $status['value'],
                'label' => $this->translator->trans($status['label'], [], 'gally_job'),
            ];
        }

        return $statuses;
    }
}
