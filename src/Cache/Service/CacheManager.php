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

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\PruneableInterface;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface as SymfonyCacheClearerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Manager for gally cache pool.
 * The class is responsible for managing and retrieving cache items from the given pool and clear
 * this cache pool globally or by tag.
 */
class CacheManager implements CacheManagerInterface, SymfonyCacheClearerInterface
{
    public function __construct(private CacheInterface $pool)
    {
    }

    public function get(string $cacheKey, callable $callback, array $tags, $ttl = null): mixed
    {
        $cacheKey = urlencode($cacheKey);
        $callback = function (ItemInterface $item, bool &$save) use ($callback, $tags, $ttl) {
            $value = $callback($tags, $ttl);
            $item->set($value);
            if (!empty($ttl)) {
                $item->expiresAfter($ttl);
            }
            if (!empty($tags)) {
                $item->tag($tags);
            }

            return $value;
        };

        return $this->pool->get($cacheKey, $callback);
    }

    public function delete(string $cacheKey): bool
    {
        return $this->pool->deleteItem($cacheKey); // @phpstan-ignore-line
    }

    public function clearTags(array $tags): bool
    {
        if ($this->pool instanceof TagAwareCacheInterface) {
            return $this->pool->invalidateTags($tags);
        }

        return false;
    }

    public function clearAll(): bool
    {
        if ($this->pool instanceof CacheItemPoolInterface) {
            return $this->pool->clear();
        }

        return false;
    }

    public function prune(): bool
    {
        if ($this->pool instanceof PruneableInterface) {
            return $this->pool->prune();
        }

        return false;
    }

    public function clear(string $cacheDir): void
    {
        $this->clearAll();
    }
}
