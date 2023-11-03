<?php

namespace Gally\Search\OpenSearch;

use OpenSearch\Namespaces\NamespaceBuilderInterface;

class ClientBuilder extends \OpenSearch\ClientBuilder
{
    /**
     * @param NamespaceBuilderInterface[] $namespaceBuilder
     */
    public function __construct(iterable $namespaceBuilders)
    {
        foreach ($namespaceBuilders as $namespaceBuilder) {
            $this->registerNamespace($namespaceBuilder);
        }
    }
}
