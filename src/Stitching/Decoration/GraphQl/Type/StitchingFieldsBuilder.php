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

namespace Gally\Stitching\Decoration\GraphQl\Type;

use ApiPlatform\GraphQl\Type\FieldsBuilderEnumInterface;
use ApiPlatform\GraphQl\Type\TypesContainerInterface;
use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Doctrine\ORM\EntityNotFoundException;
use Gally\Metadata\Constant\SourceFieldAttributeMapping;
use Gally\Metadata\Entity\Attribute\GraphQlAttributeInterface;
use Gally\Metadata\Entity\Attribute\StructuredAttributeInterface;
use Gally\Metadata\Entity\Metadata;
use Gally\Metadata\Entity\SourceField;
use Gally\Metadata\Repository\MetadataRepository;
use Gally\Metadata\Service\MetadataManager;
use Gally\ResourceMetadata\Service\ResourceMetadataManager;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;

/**
 * Allows to add dynamically attributes to an entity on GraphQL documentation.
 *
 * @todo: This is a first version of the stitching, this feature would be finalized when we will know how to manage attributes on entities.
 */
class StitchingFieldsBuilder implements FieldsBuilderEnumInterface
{
    public function __construct(
        private MetadataRepository $metadataRepository,
        private MetadataManager $metadataManager,
        private ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
        private ResourceMetadataManager $resourceMetadataManager,
        private TypesContainerInterface $typesContainer,
        private FieldsBuilderEnumInterface $decorated,
    ) {
    }

    public function getResourceObjectTypeFields(?string $resourceClass, Operation $operation, bool $input, int $depth = 0, ?array $ioMetadata = null): array
    {
        $fields = $this->decorated->getResourceObjectTypeFields($resourceClass, $operation, $input, $depth, $ioMetadata);

        $resourceMetadata = $this->resourceMetadataCollectionFactory->create($resourceClass);
        $metadataEntity = $this->resourceMetadataManager->getMetadataEntity($resourceMetadata);
        $stitchingProperty = $this->resourceMetadataManager->getStitchingProperty($resourceMetadata);

        /*
         * All these tests have been get from the "decorated" function (\ApiPlatform\GraphQl\Type\FieldsBuilder::getResourceObjectTypeFields),
         * if one of these tests is true we don't make the stitching because it's not necessary.
         *
         */
        if ((null !== $ioMetadata && \array_key_exists('class', $ioMetadata) && null === $ioMetadata['class'])
            || (null !== $operation->getName() && $input)
            || ('delete' === $operation->getName())
            || (null === $metadataEntity || null === $stitchingProperty) // Check if we have necessary ApiResource data to make the stitching.
        ) {
            return $fields;
        }

        $metadata = $this->metadataRepository->findByEntity($metadataEntity);
        if (null === $metadata) {
            throw new EntityNotFoundException(\sprintf("Entity of type '%s' for entity '%s' was not found. You should probably run migrations or fixtures?", Metadata::class, $metadataEntity));
        }

        unset($fields[$stitchingProperty]);
        $basicNestedFields = [];
        $structuredFields = [];
        /** @var SourceField $sourceField */
        foreach ($this->metadataManager->getSourceFields($metadata) as $sourceField) {
            if (!isset($fields[$sourceField->getCode()])) {
                /** @var GraphQlAttributeInterface|string|null $attributeClassType */
                $attributeClassType = SourceFieldAttributeMapping::TYPES[$sourceField->getType()] ?? null;

                if (
                    null === $attributeClassType
                    || (
                        !is_subclass_of($attributeClassType, GraphQlAttributeInterface::class)
                        && !is_subclass_of($attributeClassType, StructuredAttributeInterface::class)
                    )
                ) {
                    throw new \LogicException(\sprintf("The class '%s' doesn't implement neither the interface '%s' nor the interface '%s'", $attributeClassType, GraphQlAttributeInterface::class, StructuredAttributeInterface::class));
                }

                if (is_subclass_of($attributeClassType, StructuredAttributeInterface::class)) {
                    $structuredFields[$sourceField->getCode()] = $attributeClassType;
                } elseif (false === $sourceField->isNested()) {
                    $fields[$sourceField->getCode()] = $this->getField($attributeClassType);
                } else {
                    // There are max two levels.
                    // 'stock.qty' become $nonScalarFields['stock']['qty'].
                    [$path, $code] = [$sourceField->getNestedPath(), $sourceField->getNestedCode()];
                    $basicNestedFields[$path][$code]['source_field'] = $sourceField;
                    $basicNestedFields[$path][$code]['class_type'] = $attributeClassType;
                }
            }
        }

        $fields = $this->processStructuredFields($structuredFields, $fields);

        return $this->processBasicNestedFields($basicNestedFields, $fields);
    }

    public function getObjectType(string $typeName, string $description, array $fields): GraphQLType
    {
        if ($this->typesContainer->has($typeName)) {
            return $this->typesContainer->get($typeName);
        }

        $configuration = [
            'name' => $typeName,
            'description' => $description,
            'interfaces' => [],
        ];
        foreach ($fields as $fieldName => $field) {
            $configuration['fields'][$fieldName] = $this->getField($field['class_type']);
        }

        $objectType = new ObjectType($configuration);
        $this->typesContainer->set($typeName, $objectType);

        return $objectType;
    }

    public function processBasicNestedFields(array $basicNestedFields, array $fields): array
    {
        // This part has been inspired by the function \ApiPlatform\GraphQl\Type\TypeBuilder::getResourceObjectType.
        foreach ($basicNestedFields as $nestedPath => $children) {
            $shortName = ucfirst($nestedPath) . 'Attribute';
            $typeDescription = ucfirst($nestedPath) . ' attribute.';

            $objectType = $this->getObjectType($shortName, $typeDescription, $children);

            $fields[$nestedPath] = [
                'type' => $objectType,
                'description' => null,
                'args' => [],
                'resolve' => null,
                'deprecationReason' => null,
            ];
        }

        return $fields;
    }

    public function processStructuredFields(array $structuredFields, array $fields): array
    {
        // This part has been inspired by the function \ApiPlatform\GraphQl\Type\TypeBuilder::getResourceObjectType.
        /**
         * @var StructuredAttributeInterface $structuredAttributeClass
         */
        foreach ($structuredFields as $structuredFieldName => $structuredAttributeClass) {
            $shortName = ucfirst($structuredFieldName) . 'Attribute';
            $typeDescription = ucfirst($structuredFieldName) . ' attribute.';

            $objectType = $this->getObjectType($shortName, $typeDescription, $structuredAttributeClass::getFields());

            $parentType = $structuredAttributeClass::isList() ? GraphQLType::listOf($objectType) : $objectType;
            $fields[$structuredFieldName] = [
                'type' => $parentType,
                'description' => null,
                'args' => [],
                'resolve' => null,
                'deprecationReason' => null,
            ];
        }

        return $fields;
    }

    public function getField(
        string $attributeClassType,
        ?string $description = null,
        array $args = [],
        ?callable $resolve = null,
        ?string $deprecationReason = null
    ): array {
        // This part has been inspired by the function \ApiPlatform\GraphQl\Type\FieldsBuilder::getResourceFieldConfiguration.
        /** @var GraphQlAttributeInterface $attributeClassType */
        return [
            'type' => $attributeClassType::getGraphQlType(),
            'description' => $description,
            'args' => $args,
            'resolve' => $resolve,
            'deprecationReason' => $deprecationReason,
        ];
    }

    public function getNodeQueryFields(): array
    {
        return $this->decorated->getNodeQueryFields();
    }

    public function getItemQueryFields(string $resourceClass, Operation $operation, array $configuration): array
    {
        return $this->decorated->getItemQueryFields($resourceClass, $operation, $configuration);
    }

    public function getCollectionQueryFields(string $resourceClass, Operation $operation, array $configuration): array
    {
        return $this->decorated->getCollectionQueryFields($resourceClass, $operation, $configuration);
    }

    public function getMutationFields(string $resourceClass, Operation $operation): array
    {
        return $this->decorated->getMutationFields($resourceClass, $operation);
    }

    public function getSubscriptionFields(string $resourceClass, Operation $operation): array
    {
        return $this->decorated->getSubscriptionFields($resourceClass, $operation);
    }

    public function resolveResourceArgs(array $args, Operation $operation): array
    {
        return $this->decorated->resolveResourceArgs($args, $operation);
    }

    public function getEnumFields(string $enumClass): array
    {
        return $this->decorated->getEnumFields($enumClass);
    }
}
