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

class RelevanceConfiguration implements RelevanceConfigurationInterface
{
    public function __construct(
        protected array $relevanceConfig,
        protected FuzzinessConfigurationInterface $fuzzinessConfiguration,
    ) {
    }

    public function getMinimumShouldMatch(): string
    {
        return $this->relevanceConfig['fulltext']['minimumShouldMatch'];
    }

    public function getTieBreaker(): float
    {
        return $this->relevanceConfig['fulltext']['tieBreaker'];
    }

    public function getPhraseMatchBoost(): int|false
    {
        return !$this->relevanceConfig['phraseMatch']['enabled'] ? false : (int) $this->relevanceConfig['phraseMatch']['boost'];
    }

    public function getCutOffFrequency(): float
    {
        return $this->relevanceConfig['cutOffFrequency']['value'];
    }

    public function getFuzzinessConfiguration(): ?FuzzinessConfigurationInterface
    {
        return $this->fuzzinessConfiguration;
    }

    public function isFuzzinessEnabled(): bool
    {
        return $this->relevanceConfig['fuzziness']['enabled'];
    }

    public function isPhoneticSearchEnabled(): bool
    {
        return $this->relevanceConfig['phonetic']['enabled'];
    }

    public function getSpanNearBoost(): int|false
    {
        return $this->relevanceConfig['span']['boost'];
    }

    public function getSpanNearSlop(): int
    {
        return $this->relevanceConfig['span']['slop'];
    }

    public function isSpanNearInOrder(): bool
    {
        return $this->relevanceConfig['span']['in_order'];
    }
}
