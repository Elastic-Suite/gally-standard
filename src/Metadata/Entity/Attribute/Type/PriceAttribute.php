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

namespace Gally\Metadata\Entity\Attribute\Type;

use Gally\Metadata\Entity\Attribute\AttributeInterface;
use Gally\Metadata\Entity\Attribute\StructuredAttributeInterface;
use Gally\Search\Service\SearchContext;

/**
 * Used for normalization/de-normalization and graphql schema stitching of price source fields.
 */
class PriceAttribute extends AbstractStructuredAttribute implements AttributeInterface, StructuredAttributeInterface
{
    public const ATTRIBUTE_TYPE = 'price';

    public function __construct(
        string $attributeCode,
        mixed $value,
        protected SearchContext $searchContext
    ) {
        parent::__construct($attributeCode, $value);
    }

    public function getSanitizedData(mixed $value): mixed
    {
        $value = $this->getPriceForCurrentGroup($value);

        return parent::getSanitizedData($value);
    }

    protected function getPriceForCurrentGroup(mixed $value): mixed
    {
        $priceFound = false;
        $priceGroupId = $this->searchContext->getPriceGroup();
        if (\is_array($value) && null !== $priceGroupId) {
            foreach ($value as $priceData) {
                if (($priceData['group_id'] ?? null) == $priceGroupId) {
                    $value = [$priceData];
                    $priceFound = true;
                    break;
                }
            }
        }

        if (!\is_array($value) || null === $priceGroupId || !$priceFound) {
            $value = [];
        }

        return $value;
    }

    public static function getFields(): array
    {
        // Will depend from global configuration in the future.
        // (@see \Gally\Index\Converter\SourceField\PriceSourceFieldConverter)
        return [
            'original_price' => ['class_type' => FloatAttribute::class],
            'price' => ['class_type' => FloatAttribute::class],
            'is_discounted' => ['class_type' => BooleanAttribute::class],
            // TODO mask group_id by default ?
            'group_id' => ['class_type' => TextAttribute::class],
            // 'currency' => ['class_type' => TextAttribute:class],
            // 'is_dynamic' => ['class_type' => BooleanAttribute:class]
        ];
    }
}
