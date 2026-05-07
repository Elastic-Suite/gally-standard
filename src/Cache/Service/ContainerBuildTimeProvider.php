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
 * Provides the container build timestamp for cache-busting.
 * The value changes every time the Symfony container is recompiled
 * (automatically in dev when YAML files change, or after cache:clear in prod).
 */
class ContainerBuildTimeProvider
{
    private ?string $buildTime = null;

    public function __construct(
        private string $containerClass,
        private string $cacheDir,
    ) {
    }

    public function getBuildTime(): string
    {
        if (null === $this->buildTime) {
            $this->buildTime = $this->compute();
        }

        return $this->buildTime;
    }

    private function compute(): string
    {
        try {
            $containerFile = (new \ReflectionClass($this->containerClass))->getFileName();
            if ($containerFile && file_exists($containerFile)) {
                return (string) filemtime($containerFile);
            }
        } catch (\ReflectionException) {
        }

        if (is_dir($this->cacheDir)) {
            return (string) filemtime($this->cacheDir);
        }

        return (string) time();
    }
}
