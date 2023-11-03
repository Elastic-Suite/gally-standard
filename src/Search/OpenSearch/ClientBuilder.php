<?php

declare(strict_types=1);

namespace Gally\Search\OpenSearch;

use OpenSearch\Namespaces\NamespaceBuilderInterface;

class ClientBuilder extends \OpenSearch\ClientBuilder
{
    /**
     * @param array $esConfig
     * @param NamespaceBuilderInterface[] $namespaceBuilder
     */
    public function __construct(array $esConfig, iterable $namespaceBuilders)
    {
        $this->setHosts($esConfig['hosts']);
        foreach ($namespaceBuilders as $namespaceBuilder) {
            $this->registerNamespace($namespaceBuilder);
        }
    }
}
