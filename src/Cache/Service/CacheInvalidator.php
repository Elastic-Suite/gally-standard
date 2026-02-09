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

namespace Gally\Cache\Service;

use ApiPlatform\HttpCache\PurgerInterface as HttpPurgerInterface;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Service for global cache invalidation.
 *
 * This service allows invalidating tags or completely clearing all registered
 * cache pools in the application, as well as purging Varnish.
 *
 * IMPORTANT about Varnish:
 * - clearTags() → Purges Varnish by tags
 * - clearAll() → Does NOT purge Varnish (impossible to purge everything without knowing all tags)
 *                Varnish will naturally clear via configured TTLs.
 */
class CacheInvalidator implements CacheInvalidatorInterface
{
    /**
     * @param iterable<CacheClearerInterface>  $clearers      Symfony cache clearers
     * @param iterable<TagAwareCacheInterface> $taggablePools Taggable cache pools for invalidation by tags
     */
    public function __construct(
        private iterable $clearers,
        private iterable $taggablePools,
        private ?HttpPurgerInterface $httpPurger,
        private string $realCacheDir,
    ) {
    }

    public function clearTags(array $tags): bool
    {
        foreach ($this->taggablePools as $pool) {
            if (!$pool->invalidateTags($tags)) {
                return false;
            }
        }

        $this->httpPurger?->purge($tags);

        return true;
    }

    public function clearAll(): void
    {
        foreach ($this->clearers as $clearer) {
            $clearer->clear($this->realCacheDir);
        }
    }
}
