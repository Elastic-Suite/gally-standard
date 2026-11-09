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

use Gally\Catalog\Entity\Catalog;
use Gally\Catalog\Entity\LocalizedCatalog;
use Gally\Category\Repository\CategoryConfigurationRepository;
use Gally\Index\Entity\Index;
use Gally\Product\Service\CategoryNameUpdater;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * To avoid other test messing with the static variable inside CategoryNameUpdater (yes, static are bad).
 */
class CategoryNameUpdaterTest extends KernelTestCase
{
    public function testUpdateCategoryNames(): void
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

        $productDataBulk = [
            'a' => ['id' => 37, 'name' => 'Product 37', 'category' => [['id' => 'one']]], // Category not used in search.
            'b' => ['id' => 121, 'name' => 'Product 121', 'category' => [['id' => 'one'], ['id' => 'two', 'name' => 'Two (Bulk name)']]],
            'c' => ['id' => 2, 'name' => 'Product 2', 'category' => [['id' => 'two'], ['id' => 'three'], ['id' => 'unknown']]],
            'd' => ['id' => 42, 'name' => 'Product 42', 'category' => null],
            'e' => ['id' => 43, 'name' => 'Product 43'],
        ];

        /** @var CategoryConfigurationRepository $categoryConfigRepository */
        $categoryNameUpdater = new CategoryNameUpdater($categoryConfigRepository);

        $this->assertSame(
            [
                'a' => $productDataBulk['a'],
                'b' => [
                    'id' => 121,
                    'name' => 'Product 121',
                    'category' => [
                        ['id' => 'one'],
                        ['id' => 'two', 'name' => 'Two (Bulk name)', '_name' => 'Two (Bulk name)'],
                    ],
                ],
                'c' => [
                    'id' => 2,
                    'name' => 'Product 2',
                    'category' => [
                        ['id' => 'two', 'name' => 'Two (DB name)', '_name' => 'Two (DB name)'],
                        ['id' => 'three', 'name' => 'Three', '_name' => 'Three'],
                        ['id' => 'unknown'],
                    ],
                ],
                'd' => $productDataBulk['d'],
                'e' => $productDataBulk['e'],
            ],
            $categoryNameUpdater->updateCategoryNames($index, $productDataBulk)
        );
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
}
