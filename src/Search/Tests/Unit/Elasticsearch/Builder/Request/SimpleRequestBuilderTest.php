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

namespace Gally\Search\Tests\Unit\Elasticsearch\Builder\Request;

use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Configuration\Service\ConfigurationManager;
use Gally\Index\Api\IndexSettingsInterface;
use Gally\Metadata\Entity\Metadata;
use Gally\Metadata\Repository\MetadataRepository;
use Gally\Search\Elasticsearch\Builder\Request\Aggregation\AggregationBuilder;
use Gally\Search\Elasticsearch\Builder\Request\Query\Filter\FilterQueryBuilder;
use Gally\Search\Elasticsearch\Builder\Request\Query\Fulltext\FulltextQueryBuilder;
use Gally\Search\Elasticsearch\Builder\Request\Query\Fulltext\FuzzyFieldFilter;
use Gally\Search\Elasticsearch\Builder\Request\Query\Fulltext\NonStandardFuzzyFieldFilter;
use Gally\Search\Elasticsearch\Builder\Request\Query\Fulltext\NonStandardSearchableFieldFilter;
use Gally\Search\Elasticsearch\Builder\Request\Query\Fulltext\SearchableFieldFilter;
use Gally\Search\Elasticsearch\Builder\Request\Query\Fulltext\SpannableFieldFilter;
use Gally\Search\Elasticsearch\Builder\Request\Query\QueryBuilder;
use Gally\Search\Elasticsearch\Builder\Request\SimpleRequestBuilder;
use Gally\Search\Elasticsearch\Builder\Request\SortOrder\SortOrderBuilder;
use Gally\Search\Elasticsearch\Request\AggregationFactory;
use Gally\Search\Elasticsearch\Request\Container\Configuration\ContainerConfigurationProvider;
use Gally\Search\Elasticsearch\Request\Query\Exists;
use Gally\Search\Elasticsearch\Request\Query\Filtered;
use Gally\Search\Elasticsearch\Request\QueryFactory;
use Gally\Search\Elasticsearch\Request\QueryInterface;
use Gally\Search\Elasticsearch\RequestFactoryInterface;
use Gally\Search\Elasticsearch\Spellchecker;
use Gally\Test\AbstractTestCase;
use OpenSearch\Client;
use Psr\Log\LoggerInterface;

class SimpleRequestBuilderTest extends AbstractTestCase
{
    private static RequestFactoryInterface $requestFactory;

    private static QueryFactory $queryFactory;

    private static QueryBuilder $queryBuilder;

    private static SearchableFieldFilter $searchableFieldFilter;

    private static FuzzyFieldFilter $fuzzyFieldFilter;

    private static SpannableFieldFilter $spannableFieldFilter;

    private static NonStandardSearchableFieldFilter $nonStandardSearchableFieldFilter;

    private static NonStandardFuzzyFieldFilter $nonStandardFuzzyFieldFilter;

    private static FulltextQueryBuilder $fulltextQueryBuilder;

    private static FilterQueryBuilder $filterQueryBuilder;

    private static SortOrderBuilder $sortOrderBuilder;

    private static AggregationFactory $aggregationFactory;

    private static AggregationBuilder $aggregationBuilder;

    private static Spellchecker\RequestFactory $spellcheckRequestFactory;

    private static Spellchecker $spellchecker;

    private static ContainerConfigurationProvider $containerConfigProvider;

    private static MetadataRepository $metadataRepository;

    private static LocalizedCatalogRepository $localizedCatalogRepository;

    private static SimpleRequestBuilder $requestBuilder;

    private static LoggerInterface $logger;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        \assert(static::getContainer()->get(RequestFactoryInterface::class) instanceof RequestFactoryInterface);
        self::$requestFactory = static::getContainer()->get(RequestFactoryInterface::class);
        \assert(static::getContainer()->get(QueryFactory::class) instanceof QueryFactory);
        self::$queryFactory = static::getContainer()->get(QueryFactory::class);
        \assert(static::getContainer()->get(SearchableFieldFilter::class) instanceof SearchableFieldFilter);
        self::$searchableFieldFilter = static::getContainer()->get(SearchableFieldFilter::class);
        \assert(static::getContainer()->get(FuzzyFieldFilter::class) instanceof FuzzyFieldFilter);
        self::$fuzzyFieldFilter = static::getContainer()->get(FuzzyFieldFilter::class);
        \assert(static::getContainer()->get(SpannableFieldFilter::class) instanceof SpannableFieldFilter);
        self::$spannableFieldFilter = static::getContainer()->get(SpannableFieldFilter::class);
        self::$spannableFieldFilter = static::getContainer()->get(SpannableFieldFilter::class);
        self::$nonStandardSearchableFieldFilter = static::getContainer()->get(NonStandardSearchableFieldFilter::class);
        self::$nonStandardFuzzyFieldFilter = static::getContainer()->get(NonStandardFuzzyFieldFilter::class);
        self::$fulltextQueryBuilder = new FulltextQueryBuilder(
            static::getContainer()->get(Client::class),
            self::$queryFactory,
            self::$searchableFieldFilter,
            self::$fuzzyFieldFilter,
            self::$spannableFieldFilter,
            self::$nonStandardSearchableFieldFilter,
            self::$nonStandardFuzzyFieldFilter,
        );
        self::$filterQueryBuilder = static::getContainer()->get(FilterQueryBuilder::class);
        self::$logger = static::getContainer()->get(LoggerInterface::class);
        self::$queryBuilder = new QueryBuilder(self::$queryFactory, self::$fulltextQueryBuilder, self::$filterQueryBuilder);
        self::$sortOrderBuilder = new SortOrderBuilder(
            self::$filterQueryBuilder,
            static::getContainer()->get(ConfigurationManager::class),
            self::$logger,
        );
        \assert(static::getContainer()->get(AggregationFactory::class) instanceof AggregationFactory);
        self::$aggregationFactory = static::getContainer()->get(AggregationFactory::class);
        self::$aggregationBuilder = new AggregationBuilder(self::$aggregationFactory, self::$filterQueryBuilder);
        \assert(static::getContainer()->get('gally.search.spellchecker.request.factory') instanceof Spellchecker\RequestFactory);
        self::$spellcheckRequestFactory = static::getContainer()->get('gally.search.spellchecker.request.factory');
        \assert(static::getContainer()->get(Spellchecker::class) instanceof Spellchecker);
        self::$spellchecker = static::getContainer()->get(Spellchecker::class);
        \assert(static::getContainer()->get(IndexSettingsInterface::class) instanceof IndexSettingsInterface);
        self::$metadataRepository = static::getContainer()->get(MetadataRepository::class);
        self::$localizedCatalogRepository = static::getContainer()->get(LocalizedCatalogRepository::class);
        self::$containerConfigProvider = static::getContainer()->get(ContainerConfigurationProvider::class);
        self::$requestBuilder = new SimpleRequestBuilder(
            self::$requestFactory,
            self::$queryBuilder,
            self::$sortOrderBuilder,
            self::$aggregationBuilder,
            self::$spellcheckRequestFactory,
            self::$spellchecker,
        );

        static::loadFixture([
            __DIR__ . '/../../../../fixtures/catalogs.yaml',
            __DIR__ . '/../../../../fixtures/source_field.yaml',
            __DIR__ . '/../../../../fixtures/metadata.yaml',
        ]);
    }

    public function testInstantiate(): void
    {
        $reflector = new \ReflectionClass(SimpleRequestBuilder::class);
        $queryBuilderProperty = $reflector->getProperty('queryBuilder');
        $sortOrderBuilderProperty = $reflector->getProperty('sortOrderBuilder');
        $requestFactoryProperty = $reflector->getProperty('requestFactory');

        $simpleBuilder = new SimpleRequestBuilder(
            self::$requestFactory,
            self::$queryBuilder,
            self::$sortOrderBuilder,
            self::$aggregationBuilder,
            self::$spellcheckRequestFactory,
            self::$spellchecker,
        );
        $this->assertEquals($requestFactoryProperty->getValue($simpleBuilder), self::$requestFactory);
        $this->assertEquals($queryBuilderProperty->getValue($simpleBuilder), self::$queryBuilder);
        $this->assertEquals($sortOrderBuilderProperty->getValue($simpleBuilder), self::$sortOrderBuilder);
    }

    /**
     * @dataProvider createRequestDataProvider
     *
     * @param string $entityType        Entity type
     * @param int    $catalogId         Catalog ID
     * @param string $expectedIndexName Expected index name
     */
    public function testCreateNullQuery(string $entityType, int $catalogId, string $expectedIndexName): void
    {
        $metadata = self::$metadataRepository->findByEntity($entityType);
        $catalog = self::$localizedCatalogRepository->find($catalogId);
        $containerConfig = self::$containerConfigProvider->get($metadata, $catalog);

        $this->assertNotNull($metadata);
        $this->assertInstanceOf(Metadata::class, $metadata);
        $this->assertNotNull($metadata->getEntity());

        $request = self::$requestBuilder->create(
            $containerConfig,
            0,
            5
        );

        $this->assertEquals($request->getName(), 'generic');
        $this->assertEquals($expectedIndexName, $request->getIndex());
        $this->assertEquals(0, $request->getFrom());
        $this->assertEquals(5, $request->getSize());

        $query = $request->getQuery();
        $this->assertInstanceOf(QueryInterface::class, $query);
        $this->assertInstanceOf(Filtered::class, $query);
        /** @var Filtered $query */
        $this->assertEquals(QueryInterface::TYPE_FILTER, $query->getType());
        $this->assertNull($query->getName());
        $this->assertNull($query->getQuery());
        $this->assertNull($query->getFilter());
        $this->assertEquals(QueryInterface::DEFAULT_BOOST_VALUE, $query->getBoost());
    }

    /**
     * @dataProvider createRequestDataProvider
     *
     * @param string $entityType        Entity type
     * @param int    $catalogId         Catalog ID
     * @param string $expectedIndexName Expected index name
     */
    public function testCreateObjectQuery(string $entityType, int $catalogId, string $expectedIndexName): void
    {
        $metadata = self::$metadataRepository->findByEntity($entityType);
        $catalog = self::$localizedCatalogRepository->find($catalogId);
        $containerConfig = self::$containerConfigProvider->get($metadata, $catalog);

        $this->assertNotNull($metadata);
        $this->assertInstanceOf(Metadata::class, $metadata);
        $this->assertNotNull($metadata->getEntity());

        $request = self::$requestBuilder->create(
            $containerConfig,
            0,
            5,
            self::$queryFactory->create(QueryInterface::TYPE_EXISTS, ['field' => 'my_field'])
        );

        $this->assertEquals($request->getName(), 'generic');
        $this->assertEquals($expectedIndexName, $request->getIndex());
        $this->assertEquals(0, $request->getFrom());
        $this->assertEquals(5, $request->getSize());

        $query = $request->getQuery();
        $this->assertInstanceOf(QueryInterface::class, $query);
        $this->assertInstanceOf(Filtered::class, $query);
        /** @var Filtered $query */
        $this->assertEquals(QueryInterface::TYPE_FILTER, $query->getType());
        $this->assertNull($query->getName());
        $this->assertInstanceOf(QueryInterface::class, $query->getQuery());
        $this->assertInstanceOf(Exists::class, $query->getQuery());
        $this->assertNull($query->getFilter());
        $this->assertEquals(QueryInterface::DEFAULT_BOOST_VALUE, $query->getBoost());
    }

    // TODO: implement fulltext queries first.
    /*
    public function testCreateStringQuery(): void
    {
        $request = self::$requestBuilder->create(
            'my_index',
            0,
            5,
            'my query'
        );

        $this->assertEquals('raw', $request->getName());
        $this->assertEquals('my_index', $request->getIndex());
        $this->assertEquals(0, $request->getFrom());
        $this->assertEquals(5, $request->getSize());
    }
    */

    protected function createRequestDataProvider(): array
    {
        return [
            ['product_document', 1, 'gally_test__gally_b2c_fr_product_document'],
            ['product_document', 2, 'gally_test__gally_b2c_en_product_document'],
            ['product_document', 3, 'gally_test__gally_b2b_en_product_document'],
            ['product_document', 4, 'gally_test__gally_b2b_fr_product_document'],
            ['category', 1, 'gally_test__gally_b2c_fr_category'],
            ['category', 2, 'gally_test__gally_b2c_en_category'],
            ['category', 3, 'gally_test__gally_b2b_en_category'],
            ['category', 4, 'gally_test__gally_b2b_fr_category'],
        ];
    }
}
