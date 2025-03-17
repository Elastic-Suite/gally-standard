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

namespace Gally\Search\Service;

use Gally\Metadata\Entity\Metadata;
use Gally\Metadata\Entity\SourceField;

class ReverseSourceFieldProvider
{
    private array $sourceFieldByField = [];

    public function __construct(
        private string $nestingSeparator
    ) {
    }

    public function getSourceFieldFromFieldName(string $fieldName, Metadata $metadata): ?SourceField
    {
        $fieldName = str_replace($this->nestingSeparator, '.', $fieldName);
        if (!\array_key_exists($fieldName, $this->sourceFieldByField)) {
            $sourceField = $metadata->getSourceFieldByCodes([$fieldName, explode('.', $fieldName)[0]]);
            $this->sourceFieldByField[$fieldName] = $sourceField[0] ?? null;
        }

        return $this->sourceFieldByField[$fieldName];
    }
}
