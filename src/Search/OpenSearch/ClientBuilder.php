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

namespace Gally\Search\OpenSearch;

use OpenSearch\Namespaces\NamespaceBuilderInterface;

/**
 * Custom OpenSearch client builder that add the possibility to add custom namespaces.
 */
class ClientBuilder extends \OpenSearch\ClientBuilder
{
    /**
     * @param NamespaceBuilderInterface[] $namespaceBuilders
     */
    public function __construct(array $esConfig, iterable $namespaceBuilders)
    {
        $this->setHosts($esConfig['hosts']);
        foreach ($namespaceBuilders as $namespaceBuilder) {
            $this->registerNamespace($namespaceBuilder);
        }
    }
}
