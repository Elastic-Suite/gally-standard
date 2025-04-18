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

namespace Gally\Category\Service;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\Mapping\MappingException;
use Gally\Category\Entity\Category;
use Gally\Category\Exception\SyncCategoryException;
use Gally\Category\Repository\CategoryConfigurationRepository;
use Gally\Category\Repository\CategoryRepository;
use Gally\Index\Entity\Index;
use Gally\Metadata\Repository\MetadataRepository;
use Gally\Search\Elasticsearch\Adapter;
use Gally\Search\Elasticsearch\Builder\Request\Query\QueryBuilder;
use Gally\Search\Elasticsearch\Request\Container\Configuration\ContainerConfigurationProvider;
use Gally\Search\Elasticsearch\RequestFactoryInterface;
use Gally\Search\Entity\Document;

class CategorySynchronizer
{
    private const MAX_SAVE_BATCH_SIZE = 10000;

    private int $currentBatchSize = 1;
    private array $attachedEntities = [];

    public function __construct(
        private CategoryRepository $categoryRepository,
        private CategoryConfigurationRepository $categoryConfigurationRepository,
        private RequestFactoryInterface $requestFactory,
        private QueryBuilder $queryBuilder,
        private Adapter $adapter,
        private ContainerConfigurationProvider $containerConfigurationProvider,
        private MetadataRepository $metadataRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param array $bulkCategories category in bulk query
     *
     * @throws SyncCategoryException
     */
    public function synchronize(Index $index, array $bulkCategories = []): void
    {
        // In order to avoid memory limit error on batch action, the sql logger has been disabled.
        $this->entityManager->getConnection()->getConfiguration()->setMiddlewares([]);

        $localizedCatalog = $index->getLocalizedCatalog();
        $elasticCategories = $this->getCategoriesInElastic($index);
        $sqlCategories = $this->categoryRepository->findAllIndexedById();
        $sqlCategoryConfigurations = $this->getCategoryConfigurationsInSql($index);

        $elasticCategoryIds = array_keys($elasticCategories);
        $sqlCategoryIds = array_keys($sqlCategoryConfigurations);
        $bulkCategoryIds = [];
        array_walk(
            $bulkCategories,
            function (array $bulkCategoryData) use (&$bulkCategoryIds) {
                $bulkCategoryIds[] = $bulkCategoryData['id'];
            }
        );

        $categoriesToAdd = array_diff($elasticCategoryIds, $sqlCategoryIds);
        $categoriesToUpdate = array_diff($bulkCategoryIds ?: $elasticCategoryIds, $categoriesToAdd);
        $categoryConfigToRemove = array_diff($sqlCategoryIds, $elasticCategoryIds);

        try {
            $this->entityManager->getConnection()->beginTransaction();

            // Create and update categories and category configurations
            foreach (array_merge($categoriesToAdd, $categoriesToUpdate) as $categoryId) {
                $categoryDoc = $elasticCategories[$categoryId];
                $category = $sqlCategories[$categoryId] ?? new Category();

                if (!\array_key_exists('name', $categoryDoc->getSource())) {
                    throw new \Exception(\sprintf('No name provided for category %s', $categoryDoc->getSource()['id']));
                }

                $category->setId((string) $categoryDoc->getSource()['id']);
                $category->setParentId($categoryDoc->getSource()['parentId'] ?? '');
                $category->setLevel((int) ($categoryDoc->getSource()['level'] ?? 0));
                $category->setPath($categoryDoc->getSource()['path'] ?? '');

                $configuration = $sqlCategoryConfigurations[$categoryId] ?? new Category\Configuration();
                $configuration->setCatalog($localizedCatalog->getCatalog());
                $configuration->setLocalizedCatalog($localizedCatalog);
                $configuration->setCategory($category);
                $configuration->setName($categoryDoc->getSource()['name']);

                $this->save($configuration);
            }

            // Remove unused category configurations
            foreach ($categoryConfigToRemove as $categoryId) {
                $this->delete($sqlCategoryConfigurations[$categoryId]);
            }
            $this->flush();

            foreach ($this->categoryConfigurationRepository->getUnusedCatalogConfig() as $category) {
                $this->delete($category);
            }
            $this->flush();

            foreach ($this->categoryConfigurationRepository->getUnusedGlobalConfig() as $category) {
                $this->delete($category);
            }
            $this->flush();

            foreach ($this->categoryRepository->getUnusedCategory() as $category) {
                $this->delete($category);
            }
            $this->flush();

            $this->entityManager->getConnection()->commit();
        } catch (OptimisticLockException|ORMException|MappingException|Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            throw new SyncCategoryException($e->getMessage());
        }
    }

    /**
     * @return Category\Configuration[]
     */
    private function getCategoryConfigurationsInSql(Index $index): array
    {
        $result = [];
        $configurations = $this->categoryConfigurationRepository->findBy(['localizedCatalog' => $index->getLocalizedCatalog()]);
        array_walk(
            $configurations,
            function (Category\Configuration $categoryConfig) use (&$result) {
                $result[$categoryConfig->getCategory()->getId()] = $categoryConfig;
            }
        );

        return $result;
    }

    /**
     * @return Document[]
     */
    private function getCategoriesInElastic(Index $index): array
    {
        $elasticCategories = [];
        $page = 0;
        $pageSize = 10000;

        $containerConfig = $this->containerConfigurationProvider->get(
            $this->metadataRepository->findOneBy(['entity' => $index->getEntityType()]),
            $index->getLocalizedCatalog()
        );
        do {
            $request = $this->requestFactory->create([
                'name' => 'test',
                'indexName' => $index->getName(),
                'query' => $this->queryBuilder->createQuery($containerConfig, null, []),
                'from' => $page * $pageSize,
                'size' => $pageSize,
            ]);
            $data = iterator_to_array($this->adapter->search($request));
            array_walk(
                $data,
                function (Document $category) use (&$elasticCategories) {
                    $elasticCategories[$category->getId()] = $category;
                }
            );
            ++$page;
        } while (\count($data));

        return $elasticCategories;
    }

    /**
     * @throws OptimisticLockException
     * @throws MappingException
     * @throws ORMException
     */
    public function save(Category|Category\Configuration $entity): void
    {
        $this->batchOperation($entity, $this->entityManager->persist(...));
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws MappingException
     */
    public function delete(Category|Category\Configuration $entity): void
    {
        $this->batchOperation($entity, $this->entityManager->remove(...));
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws MappingException
     */
    private function batchOperation(Category|Category\Configuration $entity, callable $operation): void
    {
        $operation($entity);
        $this->attachedEntities[] = $entity;
        ++$this->currentBatchSize;
        if ($this->currentBatchSize > self::MAX_SAVE_BATCH_SIZE) {
            // The batch max size will not be reached during testing
            $this->flush(); // @codeCoverageIgnore
        }
    }

    private function flush(): void
    {
        $this->entityManager->flush();
        $this->currentBatchSize = 0;
        foreach ($this->attachedEntities as $entity) {
            $this->entityManager->detach($entity);
        }
        $this->attachedEntities = [];
    }
}
