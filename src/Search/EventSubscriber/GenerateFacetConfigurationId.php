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

use Doctrine\ORM\Event\PrePersistEventArgs;
use Gally\Search\Entity\Facet\Configuration;

class GenerateFacetConfigurationId
{
    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof Configuration) {
            return;
        }
        $entity->setId(
            implode(
                '-',
                [$entity->getSourceField()->getId(), $entity->getCategory() ? $entity->getCategory()->getId() : 0]
            )
        );
    }
}
