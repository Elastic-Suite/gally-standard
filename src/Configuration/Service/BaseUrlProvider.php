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

namespace Gally\Configuration\Service;

class BaseUrlProvider
{
    public function __construct(private array $baseUrl)
    {
    }

    public function getFrontUrl(): string
    {
        return rtrim($this->baseUrl['front'], '/') . '/';
    }

    public function getFrontUrlWithLanguage(string $language = 'en'): string
    {
        return $this->getFrontUrl() . $language . '/';
    }
}
