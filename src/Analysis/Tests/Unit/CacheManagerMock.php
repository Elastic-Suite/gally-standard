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

namespace Gally\Analysis\Tests\Unit;

use Gally\Cache\Service\CacheManagerInterface;

/**
 * Fake cache manager used in unit test.
 */
class CacheManagerMock implements CacheManagerInterface
{
    public function __construct()
    {
    }

    public function get(string $cacheKey, callable $callback, array $tags, $ttl = null): mixed
    {
        return $callback($tags, $ttl);
    }

    public function delete(string $cacheKey): bool
    {
        return true;
    }

    public function clearTags(array $tags): bool
    {
        return true;
    }

    public function clearAll(): bool
    {
        return true;
    }

    public function prune(): bool
    {
        return true;
    }
}
