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

namespace Gally\Metadata\Tests\Unit;

use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Index\Service\IndexSettings;
use Gally\Metadata\Repository\MetadataRepository;
use Gally\Test\AbstractTest;

class IndexSettingsTest extends AbstractTest
{
    /**
     * Test dynamic index settings.
     *
     * @dataProvider dynamicAttributeDataProvider
     */
    public function testDynamicIndexSettings(string $fieldName, int $expectedNestedFieldsLimit)
    {
        static::loadFixture(
            [
                __DIR__ . '/../fixtures/catalogs.yaml',
                __DIR__ . '/../fixtures/' . $fieldName,
                __DIR__ . '/../fixtures/metadata.yaml',
            ]);

        $indexSettings = static::getContainer()->get(IndexSettings::class);
        $metadataRepository = static::getContainer()->get(MetadataRepository::class);
        $localizedCatalogRepository = static::getContainer()->get(LocalizedCatalogRepository::class);

        $metadata = $metadataRepository->findOneBy(['entity' => 'product']);
        $localizedCatalogs = $localizedCatalogRepository->findOneBy([]);

        $this->assertEquals(
            $expectedNestedFieldsLimit,
            $indexSettings->getDynamicIndexSettings($metadata, $localizedCatalogs)['mapping.nested_fields.limit']
        );
    }

    private function dynamicAttributeDataProvider(): array
    {
        return [
            ['source_field_1.yaml', 0],
            ['source_field_2.yaml', 2],
            ['source_field_3.yaml', 4],
        ];
    }
}
