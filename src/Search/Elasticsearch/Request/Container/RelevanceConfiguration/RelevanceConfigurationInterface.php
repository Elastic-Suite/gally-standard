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

namespace Gally\Search\Elasticsearch\Request\Container\RelevanceConfiguration;

/**
 * Search Relevance configuration interface.
 * Used to retrieve relevance configuration.
 */
interface RelevanceConfigurationInterface
{
    /**
     * Retrieve minimum should match.
     */
    public function getMinimumShouldMatch(): string;

    /**
     * Retrieve Tie Breaker value.
     */
    public function getTieBreaker(): float;

    /**
     * Retrieve phrase match boost.
     */
    public function getPhraseMatchBoost(): int|false;

    /**
     * Retrieve Cut-off Frequency.
     */
    public function getCutOffFrequency(): float;

    /**
     * Check if fuzziness is enabled.
     */
    public function isFuzzinessEnabled(): bool;

    /**
     * Check if phonetic search is enabled.
     */
    public function isPhoneticSearchEnabled(): bool;

    /**
     * Retrieve Fuzziness configuration.
     */
    public function getFuzzinessConfiguration(): ?FuzzinessConfigurationInterface;

    /**
     * Retrieve span near boost value if enabled.
     */
    public function getSpanNearBoost(): int|false;

    /**
     * Retrieve span near slop value.
     */
    public function getSpanNearSlop(): int;

    /**
     * Retrieve span near in_order value.
     */
    public function isSpanNearInOrder(): bool;
}
