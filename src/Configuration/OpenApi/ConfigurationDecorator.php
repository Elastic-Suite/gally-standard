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

namespace Gally\Configuration\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use Gally\OpenApi\Helper\Documentation as DocumentationHelper;

final class ConfigurationDecorator implements OpenApiFactoryInterface
{
    public function __construct(
        private DocumentationHelper $documentationHelper,
        private OpenApiFactoryInterface $decorated
    ) {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        // Remove swagger documentation for the endpoint /category_product_merchandisings/{id}.
        $this->documentationHelper->removeEndpoint($openApi, '/configurations/{id}');

        // Remove id parameter added automatically by API Platform
        $this->documentationHelper->removeFieldFromEndpoint($openApi, '/configurations/byPath/{path}', 'id');

        return $openApi;
    }
}
