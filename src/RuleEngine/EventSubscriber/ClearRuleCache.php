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

namespace Gally\RuleEngine\EventSubscriber;

use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Gally\Cache\Service\CacheManagerInterface;
use Gally\Metadata\Model\SourceField;
use Gally\Metadata\Model\SourceFieldOption;
use Gally\RuleEngine\Service\RuleEngineManager;

/**
 * Clear the rule cache when a source field or source field option is updated or removed.
 */
class ClearRuleCache
{
    public function __construct(
        private RuleEngineManager $ruleEngineManager,
        private CacheManagerInterface $cache,
    ) {
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->clearRuleCache($args->getObject());
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $this->clearRuleCache($args->getObject());
    }

    protected function clearRuleCache(object $entity): void
    {
        if (($entity instanceof SourceField || $entity instanceof SourceFieldOption)
            && (
                ($entity instanceof SourceField && 'product' === $entity->getMetadata()->getEntity())
                || ($entity instanceof SourceFieldOption && 'product' === $entity->getSourceField()->getMetadata()->getEntity())
            )
        ) {
            $this->cache->clearTags($this->ruleEngineManager->getRuleCacheTags());
        }
    }
}
