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

namespace Gally\Metadata\Tests\Unit\Service;

use Gally\Cache\Service\CacheManagerInterface;
use Gally\Catalog\Entity\LocalizedCatalog;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Metadata\Entity\Metadata;
use Gally\Metadata\Repository\MetadataRepository;
use Gally\Metadata\Repository\SourceFieldRepository;
use Gally\Metadata\Service\SourceFieldLabelProvider;
use Gally\Test\AbstractTestCase;

class SourceFieldLabelProviderTest extends AbstractTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::loadFixture([
            __DIR__ . '/../../fixtures/source_field_label.yaml',
            __DIR__ . '/../../fixtures/source_field.yaml',
            __DIR__ . '/../../fixtures/metadata.yaml',
            __DIR__ . '/../../fixtures/catalogs.yaml',
        ]);
    }

    /**
     * Fixture facts these rely on (src/Metadata/Tests/fixtures/):
     *  - product 'name' has a label per catalog: Nom on b2c_fr, Name on b2c_en
     *  - 'stock.status' has no label row but a defaultLabel column: 'Stock status'
     *  - 'brand' has neither, so it falls back to ucfirst(code)
     *  - 'nope' does not exist, and falls back the same way
     *  - b2b_en has no label row at all, so every code falls back
     */
    public static function getLabelsDataProvider(): iterable
    {
        yield 'localized label' => [
            'b2c_fr',
            ['name'],
            [['code' => 'name', 'label' => 'Nom']],
        ];

        yield 'localized label in another catalog' => [
            'b2c_en',
            ['name'],
            [['code' => 'name', 'label' => 'Name']],
        ];

        yield 'catalog without a label row falls back to the default label column' => [
            'b2b_en',
            ['stock.status'],
            [['code' => 'stock.status', 'label' => 'Stock status']],
        ];

        yield 'no label anywhere falls back to the code' => [
            'b2c_fr',
            ['brand'],
            [['code' => 'brand', 'label' => 'Brand']],
        ];

        yield 'unknown code falls back exactly like an unlabelled field' => [
            'b2c_fr',
            ['nope', 'brand'],
            [
                ['code' => 'nope', 'label' => 'Nope'],
                ['code' => 'brand', 'label' => 'Brand'],
            ],
        ];

        yield 'the fallback only capitalises the first letter' => [
            'b2c_fr',
            ['not_a_field'],
            [['code' => 'not_a_field', 'label' => 'Not_a_field']],
        ];

        yield 'the requested order is kept' => [
            'b2c_fr',
            ['brand', 'name', 'stock.status'],
            [
                ['code' => 'brand', 'label' => 'Brand'],
                ['code' => 'name', 'label' => 'Nom'],
                ['code' => 'stock.status', 'label' => 'Stock status'],
            ],
        ];

        yield 'duplicate codes collapse to one entry' => [
            'b2c_fr',
            ['name', 'name', 'brand'],
            [
                ['code' => 'name', 'label' => 'Nom'],
                ['code' => 'brand', 'label' => 'Brand'],
            ],
        ];
    }

    /**
     * @dataProvider getLabelsDataProvider
     *
     * @param string[]                                       $codes
     * @param array<int, array{code: string, label: string}> $expectedLabels
     */
    public function testGetLabels(string $localizedCatalogCode, array $codes, array $expectedLabels): void
    {
        $provider = static::getContainer()->get('Gally\Metadata\Service\SourceFieldLabelProviderTest'); // @phpstan-ignore-line
        $localizedCatalog = static::getContainer()->get(LocalizedCatalogRepository::class)
            ->findByCodeOrId($localizedCatalogCode);

        $this->assertSame($expectedLabels, $provider->getLabels('product', $localizedCatalog, $codes));
    }

    /**
     * The category metadata also owns a 'name' source field. Asking for the product entity must
     * not pick it up.
     */
    public function testLabelsAreScopedToTheEntity(): void
    {
        $provider = static::getContainer()->get('Gally\Metadata\Service\SourceFieldLabelProviderTest'); // @phpstan-ignore-line
        $localizedCatalog = static::getContainer()->get(LocalizedCatalogRepository::class)
            ->findByCodeOrId('b2c_fr');

        // 'description' only exists on the category metadata, so for product it is an unknown code.
        $this->assertSame(
            [['code' => 'description', 'label' => 'Description']],
            $provider->getLabels('product', $localizedCatalog, ['description'])
        );
        $this->assertSame(
            [['code' => 'description', 'label' => 'Description']],
            $provider->getLabels('category', $localizedCatalog, ['description'])
        );
    }

    /**
     * Second call in the same request must not hit the repositories again.
     */
    public function testLabelMapIsBuiltOncePerRequest(): void
    {
        $metadata = (new Metadata())->setId(1)->setEntity('product');

        $metadataRepository = $this->createMock(MetadataRepository::class);
        $metadataRepository->expects($this->once())->method('findByEntity')->willReturn($metadata);

        $sourceFieldRepository = $this->createMock(SourceFieldRepository::class);
        $sourceFieldRepository->expects($this->once())->method('getRawSourceFieldLabelsByCodes')
            ->willReturn([['code' => 'brand', 'label' => 'Marque']]);

        // Always cold: the callback runs on every call, so only the local array can spare the queries.
        $cacheManager = $this->createMock(CacheManagerInterface::class);
        $cacheManager->method('get')->willReturnCallback(
            function (string $cacheKey, callable $callback) {
                $tags = [];
                $ttl = 0;

                return $callback($tags, $ttl);
            }
        );

        $localizedCatalog = $this->createMock(LocalizedCatalog::class);
        $localizedCatalog->method('getId')->willReturn(42);

        $provider = new SourceFieldLabelProvider($cacheManager, $metadataRepository, $sourceFieldRepository);

        $expected = [['code' => 'brand', 'label' => 'Marque']];
        $this->assertSame($expected, $provider->getLabels('product', $localizedCatalog, ['brand']));
        $this->assertSame($expected, $provider->getLabels('product', $localizedCatalog, ['brand']));
    }
}
