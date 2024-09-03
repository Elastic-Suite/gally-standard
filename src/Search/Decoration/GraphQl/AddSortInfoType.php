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

namespace Gally\Search\Decoration\GraphQl;

use ApiPlatform\GraphQl\Type\ContextAwareTypeBuilderInterface;
use ApiPlatform\GraphQl\Type\TypeNotFoundException;
use ApiPlatform\GraphQl\Type\TypesContainerInterface;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Gally\Search\GraphQl\Type\Definition\SortOptionType;
use Gally\Search\Model\Document;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;
use Symfony\Component\PropertyInfo\Type;

class AddSortInfoType implements ContextAwareTypeBuilderInterface
{
    public function __construct(
        private TypesContainerInterface $typesContainer,
        private SortOptionType $sortOptionType,
        private ContextAwareTypeBuilderInterface $decorated,
    ) {
    }

    public function getPaginatedCollectionType(GraphQLType $resourceType, Operation $operation): GraphQLType
    {
        $type = $this->decorated->getPaginatedCollectionType($resourceType, $operation);
        if (Document::class === $operation->getClass() || is_subclass_of($operation->getClass(), Document::class)) {
            if ($type instanceof ObjectType) {
                $fields = $type->getFields();
                if (!\array_key_exists('sortInfo', $fields)) {
                    $fields['sortInfo'] = $this->getSortingInfoType($resourceType);
                    $configuration = [
                        'name' => $type->name,
                        'description' => "Connection for {$type->name}.",
                        'fields' => $fields,
                    ];

                    $type = new ObjectType($configuration);
                    $this->typesContainer->set($type->name, $type);
                }
            }
        }

        return $type;
    }

    public function getEnumType(Operation $operation): GraphQLType
    {
        return $this->decorated->getEnumType($operation);
    }

    public function getResourceObjectType(ResourceMetadataCollection $resourceMetadataCollection, Operation $operation, ?ApiProperty $propertyMetadata = null, array $context = []): GraphQLType
    {
        return $this->decorated->getResourceObjectType($resourceMetadataCollection, $operation, $propertyMetadata, $context);
    }

    public function getNodeInterface(): InterfaceType
    {
        return $this->decorated->getNodeInterface();
    }

    public function isCollection(Type $type): bool
    {
        return $this->decorated->isCollection($type);
    }

    private function getSortingInfoType(GraphQLType $resourceType): GraphQLType
    {
        try {
            $sortInfoType = $this->typesContainer->get('sortInfo'); // @phpstan-ignore-line
        } catch (TypeNotFoundException) {
            $sortInfoType = new ObjectType(
                [
                    'name' => 'sortInfo',
                    'fields' => [
                        'current' => GraphQLType::listOf($this->sortOptionType),
                    ],
                ]
            );
            $this->typesContainer->set('sortInfo', $sortInfoType);
        }

        return GraphQLType::nonNull($sortInfoType);
    }
}
