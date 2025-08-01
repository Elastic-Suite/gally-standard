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

namespace Gally\Category\Tests\Api\Rest;

use ApiPlatform\Metadata\Get;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Category\Decoration\SyncCategoryDataAfterBulkDeleteRest;
use Gally\Category\Decoration\SyncCategoryDataAfterBulkRest;
use Gally\Category\Decoration\SyncCategoryDataAfterInstallRest;
use Gally\Category\Exception\SyncCategoryException;
use Gally\Category\Repository\CategoryProductMerchandisingRepository;
use Gally\Category\Service\CategoryProductPositionManager;
use Gally\Category\Tests\Api\GraphQl\CategorySynchronizerTest as GraphQlVersion;
use Gally\Index\Controller\RemoveIndexDocument;
use Gally\Index\Entity\IndexDocument;
use Gally\Index\Repository\Index\IndexRepository;
use Gally\Index\Service\IndexSettings;
use Gally\Index\State\DocumentProcessor;
use Gally\Index\State\InstallIndexProcessor;
use Symfony\Component\HttpFoundation\Request;

class CategorySynchronizerTest extends GraphQlVersion
{
    use IndexActions;

    /**
     * @dataProvider retryTestDataProvider
     */
    public function testSynchronizeRetry(
        string $decoratedClass,
        string $decorator,
        array $constructorParams = [],
        string $decoratedMethod = '__invoke',
    ): void {
        $catalogRepository = static::getContainer()->get(LocalizedCatalogRepository::class);
        $catalog1 = $catalogRepository->findOneBy(['code' => 'b2c_fr']);
        sleep(1); // Avoid creating two index at the same second

        $indexName = $this->createIndex('category', $catalog1->getId());
        $this->installIndex($indexName);
        $index = $this->indexRepository->findByName($indexName);

        $mutationMock = $this->getMockBuilder($decoratedClass)
            ->disableOriginalConstructor()
            ->getMock();

        if (DocumentProcessor::class === $decoratedClass) {
            $decoratedMethodParams = [new IndexDocument($indexName, []), new Get()];
            $mutationMock->method($decoratedMethod);
        } elseif (RemoveIndexDocument::class === $decoratedClass) {
            $decoratedMethodParams = [$indexName, new Request()];
            $mutationMock->method($decoratedMethod)->willReturn($index);
        } else {
            $decoratedMethodParams = [null, new Get(), []];
            $mutationMock->method($decoratedMethod)->willReturn($this->serializer->serialize($index, 'jsonld'));
        }

        $synchronizer = $this->getMockerSynchronizer(true);
        $decorator = new $decorator(
            $mutationMock,
            $synchronizer,
            ...array_map(fn ($serviceName) => static::getContainer()->get($serviceName), $constructorParams)
        );
        $decorator->{$decoratedMethod}(...$decoratedMethodParams);

        $synchronizer = $this->getMockerSynchronizer();
        $decorator = new $decorator(
            $mutationMock,
            $synchronizer,
            ...array_map(fn ($serviceName) => static::getContainer()->get($serviceName), $constructorParams)
        );
        $this->expectException(SyncCategoryException::class);
        $decorator->{$decoratedMethod}(...$decoratedMethodParams);
    }

    public function retryTestDataProvider(): iterable
    {
        yield [
            InstallIndexProcessor::class, // Decorated class
            SyncCategoryDataAfterInstallRest::class, // Decorator
            [CategoryProductPositionManager::class, 'api_platform.serializer'], // Constructor params
            'process', // Decorated method
        ];

        yield [
            DocumentProcessor::class, // Decorated class
            SyncCategoryDataAfterBulkRest::class, // Decorator
            [IndexSettings::class, IndexRepository::class, CategoryProductPositionManager::class, 'api_platform.serializer'], // Constructor params
            'process', // Decorated method
        ];

        yield [
            RemoveIndexDocument::class, // Decorated class
            SyncCategoryDataAfterBulkDeleteRest::class, // Decorator
            [IndexSettings::class, IndexRepository::class, CategoryProductMerchandisingRepository::class], // Constructor params
        ];
    }
}
