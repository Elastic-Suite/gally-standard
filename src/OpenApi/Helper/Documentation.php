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

namespace Gally\OpenApi\Helper;

use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\OpenApi;

class Documentation
{
    public function __construct(private string $routePrefix)
    {
    }

    /**
     * Allows to remove a field from swagger documentation.
     */
    public function removeFieldFromEndpoint(OpenApi $openApi, string $endpoint, string $field): void
    {
        $endpoint = $this->formatEndpointUrl($endpoint);
        $path = $openApi->getPaths()->getPath($endpoint);
        $parametersWithoutField = [];
        /** @var Parameter $parameter */
        foreach ($path->getGet()->getParameters() as $parameter) {
            if ($field !== $parameter->getName()) {
                $parametersWithoutField[] = $parameter;
            }
        }

        $openApi->getPaths()->addPath(
            $endpoint,
            $path->withGet($path->getGet()->withParameters($parametersWithoutField))
        );
    }

    /**
     * Allows to add an endpoint in swagger documentation.
     */
    public function addEndpoint(OpenApi $openApi, string $endpoint, PathItem $pathItem): void
    {
        $endpoint = $this->formatEndpointUrl($endpoint);
        $openApi->getPaths()->addPath($endpoint, $pathItem);
    }

    /**
     * Allows to remove an endpoint from swagger documentation.
     */
    public function removeEndpoint(OpenApi $openApi, string $endpoint, string $httpVerb = 'get'): void
    {
        $withFunction = 'with' . ucfirst($httpVerb);
        $endpoint = $this->formatEndpointUrl($endpoint);
        $path = $openApi->getPaths()->getPath($endpoint);
        $openApi->getPaths()->addPath($endpoint, $path->{$withFunction}(null));
    }

    private function formatEndpointUrl(string $endpoint): string
    {
        return '/' . implode('/', array_filter([trim($this->routePrefix, '/'), trim($endpoint, '/')]));
    }
}
