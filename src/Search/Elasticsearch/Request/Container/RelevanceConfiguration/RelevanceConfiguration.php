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

namespace Gally\Search\Elasticsearch\Request\Container\RelevanceConfiguration;

use Gally\Configuration\Entity\Configuration;

class RelevanceConfiguration implements RelevanceConfigurationInterface
{
    /**
     * @param Configuration[] $relevanceConfig
     */
    public function __construct(
        protected array $relevanceConfig,
        protected FuzzinessConfigurationInterface $fuzzinessConfiguration,
    ) {
        $blop = 'toto';
    }

    public function getMinimumShouldMatch(): string
    {
        $value = $this->relevanceConfig['gally.relevance.fulltext.minimumShouldMatch']->getDecodedValue();
        return $value;
    }

    public function getTieBreaker(): float
    {
        return $this->relevanceConfig['gally.relevance.fulltext.tieBreaker']->getDecodedValue();
    }

    public function getPhraseMatchBoost(): int|false
    {
        return !$this->relevanceConfig['gally.relevance.phraseMatch.enabled']->getDecodedValue()
            ? false
            : (int) $this->relevanceConfig['gally.relevance.phraseMatch.boost']->getDecodedValue();
    }

    public function getCutOffFrequency(): float
    {
        return $this->relevanceConfig['gally.relevance.cutOffFrequency.value']->getDecodedValue();
    }

    public function getFuzzinessConfiguration(): ?FuzzinessConfigurationInterface
    {
        return $this->fuzzinessConfiguration;
    }

    public function isFuzzinessEnabled(): bool
    {
        return $this->relevanceConfig['gally.relevance.fuzziness.enabled']->getDecodedValue();
    }

    public function isPhoneticSearchEnabled(): bool
    {
        return $this->relevanceConfig['gally.relevance.phonetic.enabled']->getDecodedValue();
    }

    public function getSpanNearBoost(): int|false
    {
        return $this->relevanceConfig['gally.relevance.span.boost']->getDecodedValue();
    }

    public function getSpanNearSlop(): int
    {
        return $this->relevanceConfig['gally.relevance.span.slop']->getDecodedValue();
    }

    public function isSpanNearInOrder(): bool
    {
        return $this->relevanceConfig['gally.relevance.span.in_order']->getDecodedValue();
    }
}
