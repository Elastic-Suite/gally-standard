<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade to newer versions in the future.
 *
 * @package   Elasticsuite
 * @author    ElasticSuite Team <elasticsuite@smile.fr>
 * @copyright 2023 Smile
 * @license   Licensed to Smile-SA. All rights reserved. No warranty, explicit or implicit, provided.
 *            Unauthorized copying of this file, via any medium, is strictly prohibited.
 */

declare(strict_types=1);

namespace Gally\Search\Decoration\GraphQl;

use ApiPlatform\Core\GraphQl\Type\FieldsBuilderInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Gally\Search\Model\Autocomplete;

class AutocompleteUnionFieldsBuilder implements FieldsBuilderInterface
{
    public function __construct(
        private FieldsBuilderInterface $decorated
    ) {
    }

    public function getNodeQueryFields(): array
    {
        return $this->decorated->getNodeQueryFields();
    }

    public function getItemQueryFields(string $resourceClass, ResourceMetadata $resourceMetadata, string $queryName, array $configuration): array
    {
        if ($resourceClass === Autocomplete::class) {
            $i = 0;
        }
        return $this->decorated->getItemQueryFields($resourceClass, $resourceMetadata, $queryName, $configuration);
    }

    public function getCollectionQueryFields(string $resourceClass, ResourceMetadata $resourceMetadata, string $queryName, array $configuration): array
    {
        $fields = $this->decorated->getCollectionQueryFields($resourceClass, $resourceMetadata, $queryName, $configuration);
        if ($resourceClass === Autocomplete::class) {
            $i = 0;
        }
        return $fields;
    }

    public function getMutationFields(string $resourceClass, ResourceMetadata $resourceMetadata, string $mutationName): array
    {
        return $this->decorated->getMutationFields($resourceClass, $resourceMetadata, $mutationName);
    }

    public function getSubscriptionFields(string $resourceClass, ResourceMetadata $resourceMetadata, string $subscriptionName): array
    {
        return $this->decorated->getSubscriptionFields($resourceClass, $resourceMetadata, $subscriptionName);
    }

    public function getResourceObjectTypeFields(?string $resourceClass, ResourceMetadata $resourceMetadata, bool $input, ?string $queryName, ?string $mutationName, ?string $subscriptionName, int $depth, ?array $ioMetadata): array
    {
        $fields = $this->decorated->getResourceObjectTypeFields($resourceClass, $resourceMetadata, $input, $queryName, $mutationName, $subscriptionName, $depth, $ioMetadata);
        if ($resourceClass === Autocomplete::class) {
            $i = 0;
        }
        return $fields;
    }

    public function resolveResourceArgs(array $args, string $operationName, string $shortName): array
    {
        $resourceArgs = $this->decorated->resolveResourceArgs($args, $operationName, $shortName);
        if (($operationName === 'search') && ($shortName === 'search')) {
            $i = 0;
        }
        return $resourceArgs;
    }
}
