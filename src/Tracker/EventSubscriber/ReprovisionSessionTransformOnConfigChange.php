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

namespace Gally\Tracker\EventSubscriber;

use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Configuration\Entity\Configuration;
use Gally\Tracker\Service\SessionTransformProvisioner;
use Psr\Log\LoggerInterface;

/**
 * Re-provisions every tracking_session Transform when the aggregation refresh period config changes.
 */
class ReprovisionSessionTransformOnConfigChange
{
    private const CONFIG_PATH = 'gally.tracking_session_settings.transform_schedule_period';

    private bool $configChanged = false;

    public function __construct(
        private LocalizedCatalogRepository $localizedCatalogRepository,
        private SessionTransformProvisioner $transformProvisioner,
        private LoggerInterface $logger,
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->flagIfRelevant($args->getObject());
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->flagIfRelevant($args->getObject());
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $this->flagIfRelevant($args->getObject());
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if (!$this->configChanged) {
            return;
        }

        $this->configChanged = false;

        foreach ($this->localizedCatalogRepository->findAll() as $localizedCatalog) {
            try {
                $this->transformProvisioner->createOrUpdate($localizedCatalog);
            } catch (\Exception $exception) {
                // Must not block the configuration save over a transient OpenSearch issue.
                $this->logger->error($exception);
            }
        }
    }

    private function flagIfRelevant(object $object): void
    {
        if ($object instanceof Configuration && self::CONFIG_PATH === $object->getPath()) {
            $this->configChanged = true;
        }
    }
}
