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

namespace Gally\GraphQl\Decoration\Type;

use ApiPlatform\GraphQl\Type\FieldsBuilderEnumInterface;
use ApiPlatform\GraphQl\Type\SchemaBuilderInterface;
use ApiPlatform\GraphQl\Type\TypeNotFoundException;
use ApiPlatform\GraphQl\Type\TypesContainerInterface;
use ApiPlatform\GraphQl\Type\TypesFactoryInterface;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use Gally\Configuration\Service\ConfigurationManager;
use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;

/**
 * Restricts GraphQL schema building to allowed resource classes only,
 * improving performance by skipping unneeded resources.
 */
class SchemaBuilder implements SchemaBuilderInterface
{
    public function __construct(
        private readonly ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory,
        private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
        private readonly TypesFactoryInterface $typesFactory,
        private readonly TypesContainerInterface $typesContainer,
        private readonly FieldsBuilderEnumInterface $fieldsBuilder,
        private ConfigurationManager $configurationManager,
    ) {
    }

    public function getExcludedResources(): array
    {
        return array_keys(array_filter($this->configurationManager->getScopedConfigValue('gally.graphql_excluded_resources')));
    }

    public function getSchema(): Schema
    {
        $types = $this->typesFactory->getTypes();
        foreach ($types as $typeId => $type) {
            $this->typesContainer->set($typeId, $type);
        }

        $queryFields = ['node' => $this->fieldsBuilder->getNodeQueryFields()];
        $mutationFields = [];
        $subscriptionFields = [];

        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            if (\in_array($resourceClass, $this->getExcludedResources(), true)) {
                continue;
            }

            $resourceMetadataCollection = $this->resourceMetadataCollectionFactory->create($resourceClass);
            foreach ($resourceMetadataCollection as $resourceMetadata) {
                foreach ($resourceMetadata->getGraphQlOperations() ?? [] as $operation) {
                    $configuration = null !== $operation->getArgs() ? ['args' => $operation->getArgs()] : [];

                    if ($operation instanceof Query && $operation instanceof CollectionOperationInterface) {
                        $queryFields += $this->fieldsBuilder->getCollectionQueryFields($resourceClass, $operation, $configuration);

                        continue;
                    }

                    if ($operation instanceof Query) {
                        $queryFields += $this->fieldsBuilder->getItemQueryFields($resourceClass, $operation, $configuration);

                        continue;
                    }

                    if ($operation instanceof Subscription && $operation->getMercure()) {
                        $subscriptionFields += $this->fieldsBuilder->getSubscriptionFields($resourceClass, $operation);

                        continue;
                    }

                    $mutationFields += $this->fieldsBuilder->getMutationFields($resourceClass, $operation);
                }
            }
        }

        $queryType = new ObjectType([
            'name' => 'Query',
            'fields' => $queryFields,
        ]);
        $this->typesContainer->set('Query', $queryType);

        $schema = [
            'query' => $queryType,
            'typeLoader' => function (string $typeName): ?NamedType {
                try {
                    $type = $this->typesContainer->get($typeName);
                } catch (TypeNotFoundException) {
                    return null;
                }

                return Type::getNamedType($type);
            },
        ];

        if ($mutationFields) {
            $mutationType = new ObjectType([
                'name' => 'Mutation',
                'fields' => $mutationFields,
            ]);
            $this->typesContainer->set('Mutation', $mutationType);
            $schema['mutation'] = $mutationType;
        }

        if ($subscriptionFields) {
            $subscriptionType = new ObjectType([
                'name' => 'Subscription',
                'fields' => $subscriptionFields,
            ]);
            $this->typesContainer->set('Subscription', $subscriptionType);
            $schema['subscription'] = $subscriptionType;
        }

        return new Schema($schema);
    }
}
