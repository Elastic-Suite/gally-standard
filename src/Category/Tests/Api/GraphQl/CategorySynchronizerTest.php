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

namespace Gally\Category\Tests\Api\GraphQl;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Gally\Catalog\Entity\Catalog;
use Gally\Catalog\Repository\CatalogRepository;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Category\Decoration\SyncCategoryDataAfterBulk;
use Gally\Category\Decoration\SyncCategoryDataAfterBulkDelete;
use Gally\Category\Decoration\SyncCategoryDataAfterInstall;
use Gally\Category\Entity\Category;
use Gally\Category\Entity\Category\Configuration;
use Gally\Category\Exception\SyncCategoryException;
use Gally\Category\Repository\CategoryConfigurationRepository;
use Gally\Category\Repository\CategoryProductMerchandisingRepository;
use Gally\Category\Repository\CategoryRepository;
use Gally\Category\Service\CategoryProductPositionManager;
use Gally\Category\Service\CategorySynchronizer;
use Gally\Index\MutationResolver\BulkDeleteIndexMutation;
use Gally\Index\MutationResolver\BulkIndexMutation;
use Gally\Index\MutationResolver\InstallIndexMutation;
use Gally\Index\Repository\Index\IndexRepository;
use Gally\Index\Repository\Index\IndexRepositoryInterface;
use Gally\Index\Service\IndexSettings;
use Gally\Metadata\Repository\MetadataRepository;
use Gally\Search\Elasticsearch\Adapter;
use Gally\Search\Elasticsearch\Builder\Request\Query\QueryBuilder;
use Gally\Search\Elasticsearch\Request\Container\Configuration\ContainerConfigurationProvider;
use Gally\Search\Elasticsearch\RequestFactoryInterface;
use Gally\Test\AbstractTestCase;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestGraphQlToTest;
use Gally\User\Constant\Role;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class CategorySynchronizerTest extends AbstractTestCase
{
    use IndexActions;

    protected static IndexRepositoryInterface $indexRepository;
    protected static CategoryRepository $categoryRepository;
    protected static CategoryConfigurationRepository $categoryConfigurationRepository;
    protected static SerializerInterface $serializer;

    public static function setUpBeforeClass(): void
    {
        // Use setUp instead of setupBeforeClass in order to
        // reset test data between testSynchronizeRetry and testSynchronize
        parent::setUpBeforeClass();
        \assert(static::getContainer()->get(IndexRepositoryInterface::class) instanceof IndexRepositoryInterface);
        self::$indexRepository = static::getContainer()->get(IndexRepositoryInterface::class);
        self::$categoryConfigurationRepository = static::getContainer()->get(CategoryConfigurationRepository::class);
        self::$serializer = static::getContainer()->get('api_platform.serializer');
        self::loadFixture([
            __DIR__ . '/../../fixtures/catalogs.yaml',
            __DIR__ . '/../../fixtures/source_field.yaml',
            __DIR__ . '/../../fixtures/metadata.yaml',
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        self::deleteElasticsearchFixtures();
    }

    public function testSynchronize(): void
    {
        $localizedCatalogRepository = static::getContainer()->get(LocalizedCatalogRepository::class);

        $localizedCatalog1 = $localizedCatalogRepository->findOneBy(['code' => 'b2c_fr']);
        $localizedCatalog2 = $localizedCatalogRepository->findOneBy(['code' => 'b2c_en']);
        $localizedCatalog3 = $localizedCatalogRepository->findOneBy(['code' => 'b2b_fr']);
        $category1Data = ['id' => 1, 'parentId' => null, 'level' => 1, 'name' => 'One'];
        $category2Data = ['id' => 'two', 'parentId' => null, 'level' => 1, 'name' => 'Two'];
        $category3Data = ['id' => 'three', 'parentId' => 'one', 'level' => 2, 'name' => 'Three'];
        $category4Data = ['id' => 'four', 'parentId' => 'three', 'level' => 3, 'name' => 'Four'];

        $this->validateCategoryCount(0, 0);

        // Create a non category index.
        $indexName = $this->createIndex('cms', $localizedCatalog1->getId());
        $this->installIndex($indexName);
        $this->validateCategoryCount(0, 0);

        // Create a non installed category index.
        sleep(1); // Avoid creating two indexes at the same second (on reset version the testSynchronizeRetry is executed before this test that's why we can have same b2c_fr_category indexes in the same second), to delete after the ticket #1321031 will be done
        $indexName = $this->createIndex('category', $localizedCatalog1->getId());
        $this->bulkIndex($indexName, ['one' => $category1Data, 'two' => $category2Data]);
        $this->validateCategoryCount(0, 0);

        // Install index.
        $this->installIndex($indexName);
        $this->validateCategoryCount(2, 2);

        // Add new documents in installed index.
        $this->bulkIndex($indexName, ['three' => $category3Data, 'four' => $category4Data]);
        $this->validateCategoryCount(4, 4);

        // Create an index for other catalogs.
        $this->prepareIndex($localizedCatalog2->getId(), ['one' => $category1Data, 'three' => $category3Data]);
        $this->prepareIndex($localizedCatalog3->getId(), ['one' => $category1Data, 'three' => $category3Data]);
        $this->validateCategoryCount(4, 8);

        // Remove documents.
        $this->bulkDeleteIndex($indexName, ['four']);
        $this->validateCategoryCount(3, 7);

        // Update documents on live index.
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $categoryRepository = static::getContainer()->get(CategoryRepository::class);
        $categoryConfigurationRepository = static::getContainer()->get(CategoryConfigurationRepository::class);
        $category3 = $categoryRepository->find('three');
        $categoryConfigCatalog1 = $categoryConfigurationRepository->findOneBy(
            ['category' => $category3, 'localizedCatalog' => $localizedCatalog1]
        );
        $categoryConfigCatalog1->setIsVirtual(true);
        $entityManager->persist($category3);
        $entityManager->flush();
        $this->clearRepositoryCache();

        $category3Data['name'] = 'ThreeUpdated';
        $category3Data['parentId'] = '';
        $category3Data['level'] = 1;
        $this->bulkIndex($indexName, ['three' => $category3Data]);

        $this->validateCategoryCount(3, 7);
        $category3 = $categoryRepository->find('three');
        $this->assertSame(1, $category3->getLevel());
        $category3ConfigCatalog1 = $categoryConfigurationRepository->findOneBy(
            ['category' => $category3, 'localizedCatalog' => $localizedCatalog1]
        );
        $this->assertSame('ThreeUpdated', $category3ConfigCatalog1->getName());
        $this->assertTrue($category3ConfigCatalog1->getIsVirtual());
        $categoryConfigCatalog2 = $categoryConfigurationRepository->findOneBy(
            ['category' => $category3, 'localizedCatalog' => $localizedCatalog2]
        );
        $this->assertSame('Three', $categoryConfigCatalog2->getName());
        $this->assertFalse($categoryConfigCatalog2->getIsVirtual());

        // Update category on new index.
        $category2 = $categoryRepository->find('two');
        $category2Data['name'] = 'TwoUpdated';
        sleep(1); // Avoid creating two indexes at the same second, to delete after the ticket #1321031 will be done
        $newIndexName = $this->createIndex('category', $localizedCatalog1->getId());
        $this->bulkIndex($newIndexName, ['one' => $category1Data, 'two' => $category2Data, 'three' => $category3Data]);
        $this->installIndex($newIndexName);

        $this->validateCategoryCount(3, 7);
        $category2ConfigCatalog1 = $categoryConfigurationRepository->findOneBy(
            ['category' => $category2, 'localizedCatalog' => $localizedCatalog1]
        );
        $this->assertSame('TwoUpdated', $category2ConfigCatalog1->getName());

        // Add new specific configuration on catalog scope
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $catalogRepository = static::getContainer()->get(CatalogRepository::class);
        $categoryRepository = static::getContainer()->get(CategoryRepository::class);
        $category1 = $categoryRepository->find('1');
        $category2 = $categoryRepository->find('two');
        $category3 = $categoryRepository->find('three');
        $catalog1 = $catalogRepository->findOneBy(['code' => 'b2c']);
        $catalog2 = $catalogRepository->findOneBy(['code' => 'b2b']);
        $entityManager->persist($this->createConfiguration($category1, null));
        $entityManager->persist($this->createConfiguration($category1, $catalog1));
        $entityManager->persist($this->createConfiguration($category1, $catalog2));
        $entityManager->persist($this->createConfiguration($category2, null));
        $entityManager->persist($this->createConfiguration($category2, $catalog1));
        $entityManager->persist($this->createConfiguration($category3, null));

        $entityManager->flush();
        $this->validateCategoryCount(3, 13);

        sleep(1); // Avoid creating two indexes at the same second, to delete after the ticket #1321031 will be done
        // Create new index for catalog1 without category three
        $this->prepareIndex($localizedCatalog1->getId(), ['one' => $category1Data]);
        $this->prepareIndex($localizedCatalog2->getId(), ['three' => $category3Data]);
        $this->prepareIndex($localizedCatalog3->getId(), ['three' => $category3Data, 'four' => $category4Data]);
        $this->validateCategoryCount(3, 7);
    }

    public function testSynchronizeError(): void
    {
        $synchronizer = $this->getMockerSynchronizer();

        // Test error handling
        $catalogRepository = static::getContainer()->get(LocalizedCatalogRepository::class);
        $catalog1 = $catalogRepository->findOneBy(['code' => 'b2c_fr']);
        $indexName = $this->createIndex('category', $catalog1->getId());
        $this->installIndex($indexName);
        $index = self::$indexRepository->findByName($indexName);

        $this->expectException(SyncCategoryException::class);
        $this->expectExceptionMessage('error test message');
        $synchronizer->synchronize($index);
    }

    public function testErrorWithCategoryWithoutName(): void
    {
        $localizedCatalogRepository = static::getContainer()->get(LocalizedCatalogRepository::class);
        $localizedCatalog1 = $localizedCatalogRepository->findOneBy(['code' => 'b2c_fr']);

        sleep(1); // Avoid creating two indexes at the same second, to delete after the ticket #1321031 will be done
        $indexName = $this->createIndex('category', $localizedCatalog1->getId());
        $this->bulkIndex($indexName, ['five' => ['id' => 'four', 'parentId' => 'three', 'level' => 3]]);
        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    mutation {
                      installIndex(input: {
                        name: "$indexName"
                      }) {
                        index { id name aliases }
                      }
                    }
                GQL,
                $this->getUser(Role::ROLE_ADMIN),
            ),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) {
                    $this->assertGraphQlError('No name provided for category four');
                }
            )
        );
    }

    /**
     * @dataProvider retryTestDataProvider
     */
    public function testSynchronizeRetry(string $mutationClass, string $decorator, array $constructorParams = []): void
    {
        $synchronizer = $this->getMockerSynchronizer(true);
        $catalogRepository = static::getContainer()->get(LocalizedCatalogRepository::class);
        $catalog1 = $catalogRepository->findOneBy(['code' => 'b2c_fr']);
        sleep(1); // Avoid creating two index at the same second
        $indexName = $this->createIndex('category', $catalog1->getId());
        $this->installIndex($indexName);
        $index = self::$indexRepository->findByName($indexName);

        $mutationMock = $this->getMockBuilder($mutationClass)
            ->disableOriginalConstructor()
            ->getMock();
        $mutationMock->method('__invoke')->willReturn($index);
        $decorator = new $decorator(
            $mutationMock,
            $synchronizer,
            ...$constructorParams,
        );

        $this->assertEquals($index, $decorator->__invoke(null, ['args' => ['input' => ['data' => '[]']]]));

        $synchronizer = $this->getMockerSynchronizer();
        $decorator = new $decorator(
            $mutationMock,
            $synchronizer,
            ...$constructorParams,
        );
        $this->expectException(SyncCategoryException::class);
        $decorator->__invoke(null, ['args' => ['input' => ['data' => '[]']]]);
    }

    public function retryTestDataProvider(): iterable
    {
        $indexSettings = static::getContainer()->get(IndexSettings::class);
        $indexRepository = static::getContainer()->get(IndexRepository::class);
        $categoryProductPositionManager = static::getContainer()->get(CategoryProductPositionManager::class);
        $categoryProductMerchandisingRepository = static::getContainer()->get(CategoryProductMerchandisingRepository::class);

        yield [InstallIndexMutation::class, SyncCategoryDataAfterInstall::class, [$categoryProductPositionManager]];
        yield [BulkIndexMutation::class, SyncCategoryDataAfterBulk::class, [$indexSettings, $indexRepository, $categoryProductPositionManager]];
        yield [BulkDeleteIndexMutation::class, SyncCategoryDataAfterBulkDelete::class, [$indexSettings, $indexRepository, $categoryProductMerchandisingRepository]];
    }

    protected function prepareIndex(int $catalogId, array $data): void
    {
        $indexName = $this->createIndex('category', $catalogId);
        $this->bulkIndex($indexName, $data);
        $this->installIndex($indexName);
    }

    protected function validateCategoryCount(int $categoryCount, int $categoryConfigCount): void
    {
        $categoryRepository = static::getContainer()->get(CategoryRepository::class);
        $categoryConfigurationRepository = static::getContainer()->get(CategoryConfigurationRepository::class);
        $this->assertCount($categoryCount, $categoryRepository->findAll());
        $this->assertCount($categoryConfigCount, $categoryConfigurationRepository->findAll());
    }

    protected function createConfiguration(Category $category, ?Catalog $catalog): Configuration
    {
        $config = new Configuration();
        $config->setCategory($category);
        $config->setCatalog($catalog);
        $config->setIsVirtual(true);

        return $config;
    }

    protected function clearRepositoryCache(): void
    {
        /** @var EntityManager */
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        // Clear cache and instantiate a new repository to force repository to get a fresh db object.
        $entityManager->clear();
        self::$categoryRepository = static::getContainer()->get(CategoryRepository::class);
        self::$categoryConfigurationRepository = static::getContainer()->get(CategoryConfigurationRepository::class);
    }

    protected function getMockerSynchronizer(bool $succeedOnRetry = false): CategorySynchronizer
    {
        $configurationMock = $this->getMockBuilder(\Doctrine\DBAL\Configuration::class)
            ->disableOriginalConstructor()
            ->getMock();
        $connectionMock = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $entityManagerMock = $this->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $exception = new ORMException('error test message');

        $connectionMock->method('getConfiguration')->willReturn($configurationMock);
        $entityManagerMock->method('getConnection')->willReturn($connectionMock);
        if ($succeedOnRetry) {
            $entityManagerMock
                ->method('flush')
                ->will($this->onConsecutiveCalls($this->throwException($exception), true));
        } else {
            $entityManagerMock->method('flush')->willThrowException($exception);
        }

        return new CategorySynchronizer(
            static::getContainer()->get(CategoryRepository::class),
            static::getContainer()->get(CategoryConfigurationRepository::class),
            static::getContainer()->get(RequestFactoryInterface::class),
            static::getContainer()->get(QueryBuilder::class),
            static::getContainer()->get(Adapter::class),
            static::getContainer()->get(ContainerConfigurationProvider::class),
            static::getContainer()->get(MetadataRepository::class),
            $entityManagerMock,
        );
    }
}
