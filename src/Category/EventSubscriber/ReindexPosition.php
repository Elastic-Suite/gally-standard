<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @package   Gally
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Gally\Category\EventSubscriber;

use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Gally\Category\Model\Category\ProductMerchandising;
use Gally\Category\Service\CategoryProductPositionManager;

class ReindexPosition
{
    public function __construct(
        private CategoryProductPositionManager $categoryProductPositionManager,
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->reindexPosition($args->getObject());
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->reindexPosition($args->getObject());
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $this->reindexPosition($args->getObject(), true);
    }

    private function reindexPosition(object $entity, bool $deleteMode = false): void
    {
        if (!$entity instanceof ProductMerchandising) {
            return;
        }

        $productMerchandising = $entity;
        if ($deleteMode) {
            $productMerchandising->setPosition(null);
        }

        $this->categoryProductPositionManager->reindexPosition($productMerchandising);
    }
}
