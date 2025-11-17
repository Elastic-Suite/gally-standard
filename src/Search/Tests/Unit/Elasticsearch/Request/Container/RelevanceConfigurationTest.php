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

namespace Gally\Search\Tests\Unit\Elasticsearch\Request\Container;

use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Metadata\Repository\MetadataRepository;
use Gally\Search\Elasticsearch\Request\Container\Configuration\GenericContainerConfigurationFactory;
use Gally\Search\Elasticsearch\Request\Container\RelevanceConfiguration\FuzzinessConfig;
use Gally\Search\Elasticsearch\Request\Container\RelevanceConfiguration\RelevanceConfigurationInterface;
use Gally\Test\AbstractTestCase;

class RelevanceConfigurationTest extends AbstractTestCase
{
    public static function setUpBeforeClass(): void
    {
        static::loadFixture([
            __DIR__ . '/../../../../fixtures/configurations.yaml',
            __DIR__ . '/../../../../fixtures/catalogs_relevance.yaml',
            __DIR__ . '/../../../../fixtures/source_field.yaml',
            __DIR__ . '/../../../../fixtures/metadata.yaml',
        ]);
        parent::setUpBeforeClass();
    }

    public function testRelevanceConfig()
    {
        $localizedCatalogRepository = static::getContainer()->get(LocalizedCatalogRepository::class);
        $metadataRepository = static::getContainer()->get(MetadataRepository::class);

        $metadata = $metadataRepository->findByEntity('product_document');
        $b2cEnRelevance = $localizedCatalogRepository->findOneBy(['code' => 'b2c_en_relevance']);
        $b2cFrRelevance = $localizedCatalogRepository->findOneBy(['code' => 'b2c_fr_relevance']);
        $b2bEnRelevance = $localizedCatalogRepository->findOneBy(['code' => 'b2b_en_relevance']);

        $configurationFactory = static::getContainer()->get(GenericContainerConfigurationFactory::class);

        // Check relevance config for 'b2c_en_relevance' scope + 'generic' request type.
        $containerConfig = $configurationFactory->create('generic', $metadata, $b2cEnRelevance);
        $relevanceConfig = $containerConfig->getRelevanceConfig();
        $this->checkRelevanceConfig(
            $relevanceConfig,
            [
                'fulltext_minimumShouldMatch' => '100%',
                'fulltext_tieBreaker' => 1.0,
                'phraseMatch_boost' => false,
                'fuzziness_enabled' => true,
                'fuzziness_value' => FuzzinessConfig::VALUE_AUTO,
                'fuzziness_prefixLength' => 1,
                'fuzziness_maxExpansions' => 10,
                'phonetic_enabled' => true,
            ]
        );

        // Check relevance config for 'b2c_en_relevance' scope (request type has no effect).
        $containerConfig = $configurationFactory->create('product_search_relevance', $metadata, $b2cEnRelevance);
        $relevanceConfig = $containerConfig->getRelevanceConfig();
        $this->checkRelevanceConfig(
            $relevanceConfig,
            [
                'fulltext_minimumShouldMatch' => '100%',
                'fulltext_tieBreaker' => 1.0,
                'phraseMatch_boost' => false,
                'fuzziness_enabled' => true,
                'fuzziness_value' => FuzzinessConfig::VALUE_AUTO,
                'fuzziness_prefixLength' => 1,
                'fuzziness_maxExpansions' => 10,
                'phonetic_enabled' => true,
            ]
        );

        // Check relevance config for 'b2c_fr_relevance' scope .
        $containerConfig = $configurationFactory->create('product_catalog', $metadata, $b2cFrRelevance);
        $relevanceConfig = $containerConfig->getRelevanceConfig();
        $this->checkRelevanceConfig(
            $relevanceConfig,
            [
                'fulltext_minimumShouldMatch' => '70%',
                'fulltext_tieBreaker' => 1.0,
                'phraseMatch_boost' => 25,
                'fuzziness_enabled' => true,
                'fuzziness_value' => FuzzinessConfig::VALUE_AUTO,
                'fuzziness_prefixLength' => 1,
                'fuzziness_maxExpansions' => 10,
                'phonetic_enabled' => true,
            ]
        );

        // Check relevance config for 'b2b_en_relevance'scope  (request type has no effect).
        $containerConfig = $configurationFactory->create('product_catalog', $metadata, $b2bEnRelevance);
        $relevanceConfig = $containerConfig->getRelevanceConfig();
        $this->checkRelevanceConfig(
            $relevanceConfig,
            [
                'fulltext_minimumShouldMatch' => '60%',
                'fulltext_tieBreaker' => 2.0,
                'phraseMatch_boost' => false,
                'fuzziness_enabled' => true,
                'fuzziness_value' => FuzzinessConfig::VALUE_AUTO,
                'fuzziness_prefixLength' => 1,
                'fuzziness_maxExpansions' => 10,
                'phonetic_enabled' => true,
            ]
        );
    }

    protected function checkRelevanceConfig(RelevanceConfigurationInterface $relevanceConfig, array $expectedConfig)
    {
        $this->assertEquals($expectedConfig['fulltext_minimumShouldMatch'], $relevanceConfig->getMinimumShouldMatch());
        $this->assertEquals($expectedConfig['fulltext_tieBreaker'], $relevanceConfig->getTieBreaker());
        $this->assertEquals($expectedConfig['phraseMatch_boost'], $relevanceConfig->getPhraseMatchBoost());
        $this->assertEquals($expectedConfig['fuzziness_enabled'], $relevanceConfig->isFuzzinessEnabled());
        $this->assertEquals($expectedConfig['fuzziness_value'], $relevanceConfig->getFuzzinessConfiguration()->getValue());
        $this->assertEquals($expectedConfig['fuzziness_prefixLength'], $relevanceConfig->getFuzzinessConfiguration()->getPrefixLength());
        $this->assertEquals($expectedConfig['fuzziness_maxExpansions'], $relevanceConfig->getFuzzinessConfiguration()->getMaxExpansion());
        $this->assertEquals($expectedConfig['phonetic_enabled'], $relevanceConfig->isPhoneticSearchEnabled());
    }
}
