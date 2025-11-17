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

namespace Gally\Metadata\Entity\SourceField;

use Gally\Index\Entity\Index\Mapping\FieldInterface;

class SearchAnalyzer
{
    public const SEARCH_ANALYZERS = [
        FieldInterface::ANALYZER_STANDARD,
        FieldInterface::ANALYZER_REFERENCE,
        FieldInterface::ANALYZER_EDGE_NGRAM,
    ];

    public const SEARCH_ANALYZERS_OPTIONS = [
        ['label' => 'standard', 'value' => FieldInterface::ANALYZER_STANDARD],
        ['label' => 'reference', 'value' => FieldInterface::ANALYZER_REFERENCE],
        ['label' => 'standard_edge_ngram', 'value' => FieldInterface::ANALYZER_EDGE_NGRAM],
    ];

    public static function getAvailableSearchAnalyzers(): array
    {
        return [null, ...self::SEARCH_ANALYZERS];
    }
}
