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

namespace Gally\Stitching\Serializer\GraphQl;

use ApiPlatform\GraphQl\Serializer\ItemNormalizer;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Doctrine\Common\Util\ClassUtils;
use Gally\Metadata\Repository\MetadataRepository;
use Gally\ResourceMetadata\Service\ResourceMetadataManager;
use Gally\Search\Entity\Document;
use Gally\Stitching\Service\SerializerService;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Allows to add in the GraphQL response the value of the attributes added dynamically on GraphQL documentation.
 */
class StitchingNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED_NORMALIZER = 'StitchingNormalizerCalled';

    public function __construct(
        private MetadataRepository $metadataRepository,
        private ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
        private ResourceMetadataManager $resourceMetadataManager,
        private SerializerService $serializerService
    ) {
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        // Todo: the context will disappear from this method signature in future version,
        // we'll need to find another way to check if this normalizer has already been applied on this data.
        // but the doc still recommend this solution : https://api-platform.com/docs/core/serialization/#changing-the-serialization-context-on-a-per-item-basis
        $alreadyCalled = $context[self::ALREADY_CALLED_NORMALIZER] ?? false;
        if (ItemNormalizer::FORMAT !== $format || $alreadyCalled) {
            return false;
        }

        $stitchingProperty = null;
        if (\is_object($data)) {
            // Get object glass with doctrine classUtils in order to avoid error with proxy classes
            $class = ClassUtils::getRealClass($data::class);
            $resourceMetadata = $this->resourceMetadataCollectionFactory->create($class);
            $stitchingProperty = $this->resourceMetadataManager->getStitchingProperty($resourceMetadata);
        }

        return null !== $stitchingProperty;
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $context[self::ALREADY_CALLED_NORMALIZER] = true;
        $data = $this->normalizer->normalize($object, $format, $context);

        $resourceMetadata = $this->resourceMetadataCollectionFactory->create($object::class);
        $metadataEntity = $this->resourceMetadataManager->getMetadataEntity($resourceMetadata);
        $stitchingProperty = $this->resourceMetadataManager->getStitchingProperty($resourceMetadata);
        $sourceFieldTypes = $this->serializerService->getStitchingConfigFromSourceFields($metadataEntity);

        /*
         * No need to loop here on context|attributes here if the entity-specific de-normalizer already did
         * when hydrating the object.
         */
        foreach ($object->{$stitchingProperty} as $attribute) {
            if (isset($sourceFieldTypes[$attribute->getAttributeCode()])) {
                if (\is_array($sourceFieldTypes[$attribute->getAttributeCode()])) {
                    $value = null !== $attribute->getValue() ? $attribute->getValue() : '';
                    if (\is_string($value)) {
                        $values = json_decode($value, true);
                        foreach ($sourceFieldTypes[$attribute->getAttributeCode()] as $subAttribute) {
                            $data[$attribute->getAttributeCode()][$subAttribute] = $values[$subAttribute] ?? null;
                        }
                    } else {
                        $data[$attribute->getAttributeCode()] = $attribute->getValue();
                    }
                } else {
                    $data[$attribute->getAttributeCode()] = $attribute->getValue();
                }
            }
        }

        return $data;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Document::class => false];
    }
}
