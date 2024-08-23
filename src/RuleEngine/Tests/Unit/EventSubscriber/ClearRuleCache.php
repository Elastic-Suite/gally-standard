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

namespace Gally\RuleEngine\Tests\Unit\EventSubscriber;

use Doctrine\ORM\EntityManager;
use Gally\Cache\Service\CacheManagerInterface;
use Gally\Catalog\Model\LocalizedCatalog;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Metadata\Model\Metadata;
use Gally\Metadata\Model\SourceField;
use Gally\Metadata\Model\SourceFieldOption;
use Gally\Metadata\Repository\MetadataRepository;
use Gally\Metadata\Repository\SourceFieldRepository;
use Gally\RuleEngine\Service\RuleEngineManager;
use Gally\Search\Elasticsearch\Request\Container\Configuration\ContainerConfigurationProvider;
use Gally\Search\Elasticsearch\Request\ContainerConfigurationInterface;
use Gally\Search\Elasticsearch\Request\QueryInterface;
use Gally\Test\AbstractTestCase;

class ClearRuleCache extends AbstractTestCase
{
    protected const DEFAULT_CACHE_VALUE = 'cache_empty';

    protected static RuleEngineManager $ruleEngineManager;

    protected static CacheManagerInterface $cache;

    protected static SourceFieldRepository $sourceFieldRepository;

    protected static MetadataRepository $metadataRepository;

    protected static Metadata $productMetaData;

    protected static LocalizedCatalogRepository $localizedCatalogRepository;

    protected static $defaultRule = [
        'type' => 'combination',
        'operator' => 'all',
        'value' => 'true',
        'children' => [
            [
                'type' => 'attribute',
                'field' => 'sku',
                'operator' => 'match',
                'attribute_type' => 'reference',
                'value' => 'bag',
            ],
        ],
    ];

    /**
     * @var LocalizedCatalog[]
     */
    protected static array $localizedCatalogs = [];

    protected static ContainerConfigurationProvider $containerConfigurationProvider;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        static::loadCurrentFixtures();

        self::$ruleEngineManager = static::getContainer()->get(RuleEngineManager::class);
        self::$cache = static::getContainer()->get(CacheManagerInterface::class);
        self::$sourceFieldRepository = static::getContainer()->get(SourceFieldRepository::class);
        self::$metadataRepository = static::getContainer()->get(MetadataRepository::class);
        self::$localizedCatalogRepository = static::getContainer()->get(LocalizedCatalogRepository::class);
        self::$containerConfigurationProvider = static::getContainer()->get(ContainerConfigurationProvider::class);
        self::$productMetaData = self::$metadataRepository->findOneBy(['entity' => 'product']);
    }

    protected static function loadCurrentFixtures(): void
    {
        static::loadFixture([
            __DIR__ . '/../../fixtures/catalogs.yaml',
            __DIR__ . '/../../fixtures/source_field.yaml',
            __DIR__ . '/../../fixtures/metadata.yaml',
        ]);
    }

    public function testRuleCacheFlushed()
    {
        /** @var EntityManager $entityManager */
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $categoryMetaData = self::$metadataRepository->findOneBy(['entity' => 'category']);
        $rule = self::$defaultRule;
        $localizedCatalog = $this->getLocalizedCatalog('b2c_fr');
        $containerConfig = $this->getContainerConfig($localizedCatalog);
        $this->clearCache($localizedCatalog);
        $gallyFilters = $this->generateCache($rule, $containerConfig);

        /*
         * A source field creation/update mustn't flush rule cache if its entity type is not product.
         */
        $categorySourceField = new SourceField();
        $categorySourceField->setCode('category_name');
        $categorySourceField->setMetadata($categoryMetaData);
        $this->validateResult($categorySourceField, $localizedCatalog, $rule, $gallyFilters, $entityManager);

        $categorySourceField->setDefaultLabel('Category name');
        $this->validateResult($categorySourceField, $localizedCatalog, $rule, $gallyFilters, $entityManager);

        /*
         * A source field creation mustn't flush rule cache.
         */
        $sourceField = new SourceField();
        $sourceField->setCode('promo');
        $sourceField->setMetadata(self::$productMetaData);
        $this->validateResult($sourceField, $localizedCatalog, $rule, $gallyFilters, $entityManager);

        /*
         * A source field option creation mustn't flush rule cache.
         */
        $sourceFieldOption = new SourceFieldOption();
        $sourceFieldOption->setCode('promo_options_1');
        $sourceFieldOption->setSourceField($sourceField);
        $sourceFieldOption->setDefaultLabel('Option 1');
        $sourceField->setMetadata(self::$productMetaData);
        $this->validateResult($sourceFieldOption, $localizedCatalog, $rule, $gallyFilters, $entityManager);

        /*
         * A source field update must flush rule cache.
         */
        $this->generateCache($rule, $containerConfig);
        $sourceField->setIsSearchable(true);
        $this->validateResult($sourceField, $localizedCatalog, $rule, self::DEFAULT_CACHE_VALUE, $entityManager, true);

        /*
         * A source field option update must flush rule cache.
         */
        $this->generateCache($rule, $containerConfig);
        $sourceFieldOption->setDefaultLabel('Option updated');
        $this->validateResult($sourceFieldOption, $localizedCatalog, $rule, self::DEFAULT_CACHE_VALUE, $entityManager, true);

        /*
         * A source field option deletion must flush rule cache.
         */
        $this->generateCache($rule, $containerConfig);
        $entityManager->remove($sourceFieldOption);
        $entityManager->flush();
        $gallyFiltersCached = $this->getCachedValue($rule, $localizedCatalog);
        $this->assertEquals($gallyFiltersCached, self::DEFAULT_CACHE_VALUE);
        $this->clearCache();

        /*
         * A source field deletion must flush rule cache.
         */
        $this->generateCache($rule, $containerConfig);
        $entityManager->remove($sourceField);
        $entityManager->flush();
        $gallyFiltersCached = $this->getCachedValue($rule, $localizedCatalog);
        $this->assertEquals($gallyFiltersCached, self::DEFAULT_CACHE_VALUE);
    }

    /**
     * @param SourceField|SourceFieldOption $entity
     * @param QueryInterface|string|null    $gallyFilters
     */
    protected function validateResult(object $entity, LocalizedCatalog $localizedCatalog, array $rule, mixed $gallyFilters, EntityManager $entityManager, bool $clearCache = false): void
    {
        $entityManager->persist($entity);
        $entityManager->flush();
        $gallyFiltersCached = $this->getCachedValue($rule, $localizedCatalog);
        $this->assertEquals($gallyFiltersCached, $gallyFilters);

        if ($clearCache) {
            $this->clearCache($localizedCatalog);
        }
    }

    /**
     * @return QueryInterface|string|null
     */
    protected function generateCache(array $rule, ContainerConfigurationInterface $containerConfig): mixed
    {
        $gallyFilters = self::$ruleEngineManager->transformRuleToGallyFilters($rule, $containerConfig);
        $this->assertInstanceOf(QueryInterface::class, $gallyFilters);

        return $gallyFilters;
    }

    /**
     * @return QueryInterface|string|null
     */
    protected function getCachedValue(array $rule, LocalizedCatalog $localizedCatalog): mixed
    {
        $cacheKey = self::$ruleEngineManager->getRuleCacheKey($rule, $localizedCatalog);
        $cacheTags = self::$ruleEngineManager->getRuleCacheTags($localizedCatalog);

        return self::$cache->get(
            $cacheKey,
            function (&$tags, &$ttl): mixed {
                return self::DEFAULT_CACHE_VALUE;
            },
            $cacheTags,
        );
    }

    protected function clearCache(?LocalizedCatalog $localizedCatalog = null): void
    {
        $cacheTags = self::$ruleEngineManager->getRuleCacheTags($localizedCatalog);
        self::$cache->clearTags($cacheTags);
    }

    public function getContainerConfig(LocalizedCatalog $localizedCatalog, ?string $requestType = null): ContainerConfigurationInterface
    {
        return self::$containerConfigurationProvider->get(
            self::$productMetaData,
            $localizedCatalog,
            $requestType
        );
    }

    protected function getLocalizedCatalog(string $localizedCatalogCode): LocalizedCatalog
    {
        if (!isset(self::$localizedCatalogs[$localizedCatalogCode])) {
            self::$localizedCatalogs[$localizedCatalogCode] = self::$localizedCatalogRepository->findOneBy(['code' => $localizedCatalogCode]);
        }

        return self::$localizedCatalogs[$localizedCatalogCode];
    }
}
