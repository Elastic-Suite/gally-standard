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

namespace Gally\RequestContext\Service;

use Gally\Configuration\Service\ConfigurationManager;
use Symfony\Component\HttpFoundation\RequestStack;

class RequestContextManager
{
    public function __construct(
        private RequestStack $requestStack,
        private ConfigurationManager $configurationManager,
    ) {
    }

    public function getHeaders(): array
    {
        return $this->configurationManager->getScopedConfigValue('gally.request_context.headers') ?? [];
    }

    public function getContext(): array
    {
        $context = [];
        $request = $this->requestStack->getCurrentRequest();
        foreach ($this->getHeaders() as $header) {
            if (null != $request->headers->get($header)) {
                $context[$header] = $request->headers->get($header);
            }
        }

        return $context;
    }

    public function getContextByHeader(string $header): mixed
    {
        return $this->getContext()[$header] ?? null;
    }
}
