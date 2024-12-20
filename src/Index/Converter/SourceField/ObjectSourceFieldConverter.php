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

namespace Gally\Index\Converter\SourceField;

use Gally\Metadata\Entity\SourceField;

class ObjectSourceFieldConverter implements SourceFieldConverterInterface
{
    public function supports(SourceField $sourceField): bool
    {
        return SourceField\Type::TYPE_OBJECT === $sourceField->getType();
    }

    public function getFields(SourceField $sourceField): array
    {
        // TODO: Implement getFields() method.
        return [];
    }
}
