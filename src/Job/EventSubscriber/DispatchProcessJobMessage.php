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

namespace Gally\Job\EventSubscriber;

use Doctrine\ORM\Event\PostPersistEventArgs;
use Gally\Job\Entity\Job;
use Gally\Job\Message\ProcessJob;
use Symfony\Component\Messenger\MessageBusInterface;

class DispatchProcessJobMessage
{
    public function __construct(
        private MessageBusInterface $bus,
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof Job) {
            return;
        }

        $this->bus->dispatch(new ProcessJob($entity->getId()));
    }
}
