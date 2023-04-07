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

namespace Gally\RuleEngine\EventSubscriber;

use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Gally\Cache\Service\CacheManagerInterface;
use Gally\Metadata\Model\SourceField;
use Gally\Metadata\Model\SourceFieldOption;
use Gally\RuleEngine\Service\RuleEngineManager;

/**
 * Clear the rule cache when a source field or source field option is updated or removed.
 */
class ClearRuleCache implements EventSubscriberInterface
{
    public function __construct(
        private RuleEngineManager $ruleEngineManager,
        private CacheManagerInterface $cache,
    ) {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postUpdate,
            Events::postRemove,
        ];
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $this->clearRuleCache($args);
    }

    public function postRemove(LifecycleEventArgs $args): void
    {
        $this->clearRuleCache($args);
    }

    protected function clearRuleCache(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
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
