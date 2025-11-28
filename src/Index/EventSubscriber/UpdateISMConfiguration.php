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

namespace Gally\Index\EventSubscriber;

use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Configuration\Entity\Configuration;
use Gally\Index\Api\IndexSettingsInterface;
use Gally\Index\Repository\IndexStateManagement\IndexStateManagementRepositoryInterface;
use Gally\Metadata\Repository\MetadataRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Update ISM configuration on config update.
 */
class UpdateISMConfiguration implements CacheWarmerInterface
{
    public function __construct(
        private MetadataRepository $metadataRepository,
        private LocalizedCatalogRepository $localizedCatalogRepository,
        private IndexStateManagementRepositoryInterface $indexStateManagementRepository,
        private IndexSettingsInterface $indexSettings,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Update ISM conf on configuration created/update from gally configuration screen in BO.
     */
    public function postPersist(PostPersistEventArgs $args): void
    {
        $conf = $args->getObject();
        if (!$conf instanceof Configuration) {
            return;
        }

        if (str_starts_with($conf->getPath(), 'gally.ism_settings.')) {
            $this->updateIndexTemplateMapping();
        }
    }

    /**
     * Update ISM conf on configuration created/update from gally configuration screen in BO.
     */
    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $conf = $args->getObject();
        if (!$conf instanceof Configuration) {
            return;
        }

        if (str_starts_with($conf->getPath(), 'gally.ism_settings.')) {
            $this->updateIndexTemplateMapping();
        }
    }

    /**
     * Update ISM conf on cache warmup to match default configuration define in YAML files.
     */
    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        $this->updateIndexTemplateMapping();

        return [];
    }

    public function isOptional(): bool
    {
        return false;
    }

    private function updateIndexTemplateMapping(): void
    {
        try {
            foreach ($this->metadataRepository->findBy(['isTimeSeriesData' => true]) as $timeSeriesMetadata) {
                foreach ($this->localizedCatalogRepository->findAll() as $localizedCatalog) {
                    $ismPolicy = $this->indexStateManagementRepository->findByMetadata($timeSeriesMetadata, $localizedCatalog);
                    if (null === $ismPolicy) {
                        continue;
                    }

                    $ismPolicy
                        ->setRolloverAfter($this->indexSettings->getIsmRolloverAfter($localizedCatalog, $timeSeriesMetadata))
                        ->setDeleteAfter($this->indexSettings->getIsmDeleteAfter($localizedCatalog, $timeSeriesMetadata));

                    $this->indexStateManagementRepository->update($ismPolicy);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
