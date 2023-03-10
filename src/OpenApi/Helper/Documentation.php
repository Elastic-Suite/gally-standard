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

namespace Gally\OpenApi\Helper;

use ApiPlatform\Core\Documentation\DocumentationInterface;
use ApiPlatform\Core\OpenApi\Model\Parameter;
use ApiPlatform\Core\OpenApi\OpenApi;

class Documentation
{
    /**
     * Allows to remove a field from swagger documentation.
     */
    public function removeFieldFromEndpoint(DocumentationInterface $openApi, string $endpoint, string $field): void
    {
        /** @var OpenApi $openApi */
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
     * Allows to remove an endpoint from swagger documentation.
     */
    public function removeEndpoint(DocumentationInterface $openApi, string $endpoint, string $httpVerb = 'get'): void
    {
        /** @var OpenApi $openApi */
        $withFunction = 'with' . ucfirst($httpVerb);
        $path = $openApi->getPaths()->getPath($endpoint);
        $openApi->getPaths()->addPath($endpoint, $path->{$withFunction}(null));
    }
}
