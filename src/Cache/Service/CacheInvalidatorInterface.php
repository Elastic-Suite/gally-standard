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

/**
 * Service to manage cache invalidation of all tagged cache pools + Varnish.
 */
interface CacheInvalidatorInterface
{
    /**
     * Invalid cache tags in every cache pool and http cache.
     *
     * @param string[] $tags cache tags to invalidate
     *
     * @return bool True on success
     */
    public function clearTags(array $tags): bool;

    /**
     * Remove all objects from the cache pool but not http cache.
     *
     * IMPORTANT: This method does NOT purge Varnish because it's impossible
     * to purge all of Varnish without knowing all existing tags.
     * Varnish will naturally clear via configured TTLs.
     */
    public function clearAll(): void;
}
