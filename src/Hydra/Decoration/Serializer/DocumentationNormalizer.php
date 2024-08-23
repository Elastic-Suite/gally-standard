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

namespace Gally\Hydra\Decoration\Serializer;

use ApiPlatform\Hydra\Serializer\DocumentationNormalizer as BaseDocumentationNormalizer;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Allows to add extra data in Hydra API documentation.
 */
class DocumentationNormalizer implements NormalizerInterface
{
    public function __construct(
        private ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
        private PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory,
        private PropertyMetadataFactoryInterface $propertyMetadataFactory,
        private NormalizerInterface $decorated,
        private ?NameConverterInterface $nameConverter = null,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        // We get the documentation generated by the decorated service (native documentation).
        $documentation = $this->decorated->normalize($object, $format, $context);

        /*
         * We loop on the "ApiResources" as in the service decorated.
         * @see \ApiPlatform\Hydra\Serializer\DocumentationNormalizer::normalize
         */
        foreach ($object->getResourceNameCollection() as $resourceClass) {
            $resourceMetadataCollection = $this->resourceMetadataCollectionFactory->create($resourceClass);
            $resourceMetadata = $resourceMetadataCollection[0];
            $shortName = $resourceMetadata->getShortName();
            $prefixedShortName = $resourceMetadata->getTypes()[0] ?? "#$shortName";

            // Custom function to add hydra custom documentation.
            $this->updateHydraProperties($documentation, $resourceClass, $resourceMetadata, $shortName, $prefixedShortName, $context);
        }

        return $documentation;
    }

    /**
     * Update Hydra properties from the attributes get from ApiProperty.
     */
    private function updateHydraProperties(array &$documentation, string $resourceClass, ApiResource $resourceMetadata, string $shortName, string $prefixedShortName, array $context): void
    {
        // We get the key of the class "$shortName" from documentation array.
        $classKey = array_search($shortName, array_column($documentation['hydra:supportedClass'] ?? [], 'hydra:title'), true);
        if (false === $classKey) {
            return;
        }

        /**
         * The following code is inspired by @see \ApiPlatform\Hydra\Serializer\DocumentationNormalizer::getHydraProperties
         * The goal is to parse the ApiResource "$shortName" and get the metadata of properties.
         */
        $classes[$resourceClass] = true;
        foreach ($resourceMetadata->getOperations() as $operation) {
            /** @var Operation $operation */
            if (!$operation instanceof CollectionOperationInterface) {
                continue;
            }

            $inputMetadata = $operation->getInput();
            if (null !== $inputClass = $inputMetadata['class'] ?? null) {
                $classes[$inputClass] = true;
            }

            $outputMetadata = $operation->getOutput();
            if (null !== $outputClass = $outputMetadata['class'] ?? null) {
                $classes[$outputClass] = true;
            }
        }

        /** @var string[] $classes */
        $classes = array_keys($classes);
        foreach ($classes as $class) {
            // Add gally documentation at class level.
            $classDoc = array_replace_recursive(
                $documentation['hydra:supportedClass'][$classKey],
                $resourceMetadata->getExtraProperties()['hydra:supportedClass'] ?? [],
            );
            $documentation['hydra:supportedClass'][$classKey] = $classDoc;

            foreach ($this->propertyNameCollectionFactory->create($class, $this->getPropertyMetadataFactoryContext($resourceMetadata)[0]) as $propertyName) {
                $propertyMetadata = $this->propertyMetadataFactory->create($class, $propertyName);
                $hydraSupportedProperty = $propertyMetadata->getExtraProperties()['hydra:supportedProperty'] ?? null;
                if (!\is_array($hydraSupportedProperty)) {
                    continue;
                }

                if (true === $propertyMetadata->isIdentifier() && false === $propertyMetadata->isWritable()) {
                    continue;
                }

                if ($this->nameConverter) {
                    /** @var MetadataAwareNameConverter $nameConverter */
                    $nameConverter = $this->nameConverter;
                    $propertyName = $nameConverter->normalize($propertyName, $class, BaseDocumentationNormalizer::FORMAT, $context);
                }

                // We get the key of the property "$propertyName" from documentation array.
                $propertyKey = array_search($propertyName, array_column($documentation['hydra:supportedClass'][$classKey]['hydra:supportedProperty'] ?? [], 'hydra:title'), true);
                if (false !== $propertyKey) {
                    // Add gally documentation at property level.
                    // In $documentation, we add the documentation get from the metadata property on the ApiResource.
                    $propertyDoc = array_replace_recursive(
                        $documentation['hydra:supportedClass'][$classKey]['hydra:supportedProperty'][$propertyKey],
                        $hydraSupportedProperty
                    );
                    $documentation['hydra:supportedClass'][$classKey]['hydra:supportedProperty'][$propertyKey] = $propertyDoc;
                }
            }
        }
    }

    /**
     * Creates context for property metatata factories.
     * Copy/Paste as this function is private.
     *
     * @see \ApiPlatform\Hydra\Serializer\DocumentationNormalizer::getPropertyMetadataFactoryContext
     */
    private function getPropertyMetadataFactoryContext(ApiResource $resourceMetadata): array
    {
        $normalizationGroups = $resourceMetadata->getNormalizationContext()[AbstractNormalizer::GROUPS] ?? null;
        $denormalizationGroups = $resourceMetadata->getDenormalizationContext()[AbstractNormalizer::GROUPS] ?? null;
        $propertyContext = [
            'normalization_groups' => $normalizationGroups,
            'denormalization_groups' => $denormalizationGroups,
        ];
        $propertyNameContext = [];

        if ($normalizationGroups) {
            $propertyNameContext['serializer_groups'] = $normalizationGroups;
        }

        if (!$denormalizationGroups) {
            return [$propertyNameContext, $propertyContext];
        }

        if (!isset($propertyNameContext['serializer_groups'])) {
            $propertyNameContext['serializer_groups'] = $denormalizationGroups;

            return [$propertyNameContext, $propertyContext];
        }

        foreach ($denormalizationGroups as $group) {
            $propertyNameContext['serializer_groups'][] = $group;
        }

        return [$propertyNameContext, $propertyContext];
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        /** @var BaseDocumentationNormalizer $decorated */
        $decorated = $this->decorated;

        return $decorated->supportsNormalization($data, $format, $context);
    }

    public function getSupportedTypes(?string $format): array
    {
        return $this->decorated->getSupportedTypes($format);
    }
}
