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

namespace Gally\Entity\Service;

use Gally\RequestContext\Service\RequestContextManager;

class ReferenceLocationProvider
{
    public const REFERENCE_LOCATION = 'reference-location';

    public function __construct(
        private RequestContextManager $requestContextManager,
        private string $defaultReferenceLocation,
    ) {
    }

    public function getReferenceLocation(): ?string
    {
        return $this->requestContextManager->getContextByHeader(self::REFERENCE_LOCATION) ?? $this->defaultReferenceLocation;
    }
}
