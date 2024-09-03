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

namespace Gally\Product\Tests\Unit\Service;

use Gally\Catalog\Model\Catalog;
use Gally\Catalog\Model\LocalizedCatalog;
use Gally\Category\Repository\CategoryConfigurationRepository;
use Gally\Index\Dto\Bulk;
use Gally\Index\Model\Index;
use Gally\Index\Repository\Index\IndexRepositoryInterface;
use Gally\Product\Service\CategoryNameUpdater;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * To avoid other test messing with the static variable inside CategoryNameUpdater (yes, static are bad).
 */
class CategoryNameUpdaterTest extends KernelTestCase
{
    public function testUpdateSuccess(): void
    {
        $localizedCatalog = $this->getMockLocalizedCatalog();
        $localizedCatalog->method('getCatalog')->willReturn(new Catalog());
        $localizedCatalog->method('getId')->willReturn(1);

        $index = new Index('my_product_index');
        /** @var LocalizedCatalog $localizedCatalog */
        $index->setLocalizedCatalog($localizedCatalog);

        $categoryConfigRepository = $this->getMockCategoryConfigurationRepository();
        $categoryConfigRepository->method('findMergedByContext')
            ->willReturn([
                ['id' => 1, 'category_id' => 'one', 'name' => 'One', 'useNameInProductSearch' => 0],
                ['id' => 2, 'category_id' => 'two', 'name' => 'Two (DB name)', 'useNameInProductSearch' => 1],
                ['id' => 5, 'category_id' => 'three', 'name' => 'Three', 'useNameInProductSearch' => 1],
            ]);
        $indexRepository = $this->getMockIndexRepository();
        $logger = $this->getMockLogger();

        $productDataBulk = [
            ['id' => 37, 'name' => 'Product 37', 'category' => [['id' => 'one']]], // No expected update.
            ['id' => 121, 'name' => 'Product 121', 'category' => [['id' => 'one'], ['id' => 'two', 'name' => 'Two (Bulk name)']]],
            ['id' => 2, 'name' => 'Product 2', 'category' => [['id' => 'two', 'name' => 'Two (Bulk name)'], ['id' => 'three']]],
        ];

        $expectedUpdateBulkRequest = new Bulk\Request();
        $expectedUpdateBulkRequest->updateDocuments(
            $index,
            [
                [
                    'id' => 121,
                    'category' => [
                        ['id' => 'one'],
                        ['id' => 'two', 'name' => 'Two (Bulk name)', '_name' => 'Two (Bulk name)'],
                    ],
                ],
                [
                    'id' => 2,
                    'category' => [
                        ['id' => 'two', 'name' => 'Two (Bulk name)', '_name' => 'Two (Bulk name)'],
                        ['id' => 'three', 'name' => 'Three', '_name' => 'Three'],
                    ],
                ],
            ]
        );

        $indexRepository->expects($this->once())->method('bulk')->with($expectedUpdateBulkRequest);

        /** @var CategoryConfigurationRepository $categoryConfigRepository */
        /** @var IndexRepositoryInterface $indexRepository */
        /** @var LoggerInterface $logger */
        $categoryNameUpdater = new CategoryNameUpdater($categoryConfigRepository, $indexRepository, $logger);
        $categoryNameUpdater->updateCategoryNames($index, $productDataBulk);
    }

    public function testUpdateFailure(): void
    {
        $localizedCatalog = $this->getMockLocalizedCatalog();
        $localizedCatalog->method('getCatalog')->willReturn(new Catalog());
        $localizedCatalog->method('getId')->willReturn(1);

        $index = new Index('my_product_index');
        /** @var LocalizedCatalog $localizedCatalog */
        $index->setLocalizedCatalog($localizedCatalog);

        $categoryConfigRepository = $this->getMockCategoryConfigurationRepository();
        $categoryConfigRepository->method('findMergedByContext')
            ->willReturn([
                ['id' => 1, 'category_id' => 'one', 'name' => 'One', 'useNameInProductSearch' => 0],
                ['id' => 2, 'category_id' => 'two', 'name' => 'Two (DB name)', 'useNameInProductSearch' => 1],
                ['id' => 5, 'category_id' => 'three', 'name' => 'Three', 'useNameInProductSearch' => 1],
            ]);

        $indexRepository = $this->getMockIndexRepository();
        $indexRepository->method('bulk')
            ->willReturn(new Bulk\Response([
                'took' => 30,
                'errors' => true,
                'items' => [
                    [
                        'update' => [
                            '_index' => $index->getName(),
                            '_type' => '_doc',
                            '_id' => '5',
                            'status' => 404,
                            'error' => [
                                'type' => 'document_missing_exception',
                                'reason' => '[5]: document missing',
                                'index_uuid' => 'aAsFqTI0Tc2W0LCWgPNrOA',
                                'shard' => '0',
                            ],
                        ],
                    ],
                ],
            ]));

        $logger = $this->getMockLogger();

        $productDataBulk = [
            ['id' => 37, 'name' => 'Product 37', 'category' => [['id' => 'one']]], // No expected update.
            ['id' => 121, 'name' => 'Product 121', 'category' => [['id' => 'one'], ['id' => 'two']]],
            ['id' => 2, 'name' => 'Product 2'], // No expected update.
        ];

        $logger->expects($this->exactly(3))->method('error')->withConsecutive(
            ['Bulk update operation failed 1 times in index my_product_index.', []],
            ['Error (document_missing_exception) : [5]: document missing.', []],
            ['Failed doc ids sample : 5.', []],
        );

        /** @var CategoryConfigurationRepository $categoryConfigRepository */
        /** @var IndexRepositoryInterface $indexRepository */
        /** @var LoggerInterface $logger */
        $categoryNameUpdater = new CategoryNameUpdater($categoryConfigRepository, $indexRepository, $logger);
        $categoryNameUpdater->updateCategoryNames($index, $productDataBulk);
    }

    private function getMockLocalizedCatalog(): MockObject
    {
        return $this->getMockBuilder(LocalizedCatalog::class)->getMock();
    }

    private function getMockCategoryConfigurationRepository(): MockObject
    {
        return $this->getMockBuilder(CategoryConfigurationRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getMockIndexRepository(): MockObject
    {
        return $this->getMockBuilder(IndexRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getMockLogger(): MockObject
    {
        return $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
