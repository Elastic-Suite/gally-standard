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

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\GraphQl\Type\TypeBuilderInterface;
use ApiPlatform\Core\GraphQl\Type\TypesContainerInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Gally\Category\Model\Category;
use Gally\Product\Model\Product;
use Gally\Search\Model\Autocomplete;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type as GraphQLType;
use GraphQL\Type\Definition\UnionType;
use GraphQL\Type\Definition\ObjectType;
use Symfony\Component\PropertyInfo\Type;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;

class AutocompleteUnionTypeBuilder implements TypeBuilderInterface
{
    public function __construct(
        private ResourceMetadataFactoryInterface $resourceMetadataFactory,
        private TypesContainerInterface $typesContainer,
        private TypeBuilderInterface $decorated
    ) {
    }

    public function getResourceObjectType(?string $resourceClass, ResourceMetadata $resourceMetadata, bool $input, ?string $queryName, ?string $mutationName, ?string $subscriptionName, bool $wrapped, int $depth): GraphQLType
    {
        $type = $this->decorated->getResourceObjectType(
            $resourceClass,
            $resourceMetadata,
            $input,
            $queryName,
            $mutationName,
            $subscriptionName,
            $wrapped,
            $depth
        );
        if ($resourceClass === Autocomplete::class) {
            $i = 0;
            $subTypes = [];
            foreach ([Product::class, Category::class] as $subResourceClass) {
                try {
                    $subResource = $this->resourceMetadataFactory->create($subResourceClass);
                } catch (ResourceClassNotFoundException $e) {
                    continue;
                }
                $typeName = $subResource->getShortName();
                if ($this->typesContainer->has($typeName)) {
                    $subTypes[] = $this->typesContainer->get($typeName);
                    continue;
                }
                $subType = $this->decorated->getResourceObjectType($subResourceClass, $subResource, false, null, null, null, false, $depth);
                $subTypes[] = $subType;
            };

            $unionType = new UnionType([
               'name' => 'AutocompleteUnion',
               'types' => $subTypes,
               /*
               'resolveType' => function ($value): ObjectType {
                    switch ($value->type ?? null) {
                        case 'story': return MyTypes::story();
                        case 'user': return MyTypes::user();
                        default: throw new Exception("Unexpected AutocompleteUnion type: {$value->type ?? null}");
                    }
                },
               */
            ]);

            $type = $unionType;
        }
        return $type;
    }

    public function getNodeInterface(): InterfaceType
    {
        return $this->decorated->getNodeInterface();
    }

    public function getResourcePaginatedCollectionType(GraphQLType $resourceType, string $resourceClass, string $operationName): GraphQLType
    {
        $type = $this->decorated->getResourcePaginatedCollectionType($resourceType, $resourceClass, $operationName);
        if ($resourceClass === Autocomplete::class) {
            $i = 0;
        }
        return $type;
    }

    public function isCollection(Type $type): bool
    {
        return $this->decorated->isCollection($type);
    }
}
