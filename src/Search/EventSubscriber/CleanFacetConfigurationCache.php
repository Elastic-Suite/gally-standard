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

namespace Gally\Search\EventSubscriber;

use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Gally\Search\Entity\Facet\Configuration;
use Gally\Search\Service\FacetConfigurationManager;

class CleanFacetConfigurationCache
{
    public function __construct(
        private FacetConfigurationManager $facetConfigurationManager,
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->cleanFacetConfigurationCache($args->getObject());
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->cleanFacetConfigurationCache($args->getObject());
    }

    private function cleanFacetConfigurationCache(object $entity): void
    {
        if (!$entity instanceof Configuration) {
            return;
        }

        $this->facetConfigurationManager->cleanCache();
    }
}
