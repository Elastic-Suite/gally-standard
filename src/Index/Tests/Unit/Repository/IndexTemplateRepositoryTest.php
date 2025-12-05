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

namespace Gally\Index\Tests\Unit\Repository;

use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Index\Repository\IndexTemplate\IndexTemplateRepositoryInterface;
use Gally\Metadata\Repository\MetadataRepository;
use Gally\Test\AbstractTestCase;

class IndexTemplateRepositoryTest extends AbstractTestCase
{
    private static IndexTemplateRepositoryInterface $repository;

    private static array $testTemplates = [
        'gally_test__gally_b2c_fr_event',
        'gally_test__gally_dummy-1',
        'gally_test__gally_dummy-2',
        'dummy-1',
        'dummy-2',
    ];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        \assert(static::getContainer()->get(IndexTemplateRepositoryInterface::class) instanceof IndexTemplateRepositoryInterface);
        self::$repository = static::getContainer()->get(IndexTemplateRepositoryInterface::class);
        self::cleanupTestTemplates();

        self::loadFixture([
            __DIR__ . '/../../fixtures/catalogs.yaml',
            __DIR__ . '/../../fixtures/source_field.yaml',
            __DIR__ . '/../../fixtures/metadata.yaml',
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        self::cleanupTestTemplates();
        parent::tearDownAfterClass();
    }

    public function testCreateEntityIndexTemplate(): void
    {
        $localizedCatalogRepository = static::getContainer()->get(LocalizedCatalogRepository::class);
        $metadataRepository = static::getContainer()->get(MetadataRepository::class);

        $metadataEvent = $metadataRepository->findByEntity('event');
        $b2cFrLocalizedCatalog = $localizedCatalogRepository->findOneBy(['code' => 'b2c_fr']);

        $createdTemplate = self::$repository->createEntityIndexTemplate(
            $metadataEvent,
            $b2cFrLocalizedCatalog,
            ['test-event-*']
        );

        $this->assertEquals('gally_test__gally_b2c_fr_event', $createdTemplate->getName());
        $this->assertEquals(['test-event-*'], $createdTemplate->getIndexPatterns());
        $this->assertEquals($b2cFrLocalizedCatalog, $createdTemplate->getLocalizedCatalog());
        $this->assertEquals($metadataEvent->getEntity(), $createdTemplate->getEntityType());
        $this->assertEquals(['.entity_event', '.catalog_1'], $createdTemplate->getAliases());
        $this->assertArrayHasKey('id', $createdTemplate->getMappings()['properties']);
        $this->assertArrayHasKey('@timestamp', $createdTemplate->getMappings()['properties']);
        $this->assertArrayHasKey('event_type', $createdTemplate->getMappings()['properties']);
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(string $identifier, array $indexPatterns, array $settings = [], array $mappings = []): void
    {
        $localizedCatalogRepository = static::getContainer()->get(LocalizedCatalogRepository::class);

        $savedTemplate = self::$repository->createIndexTemplate(
            $identifier,
            $localizedCatalogRepository->findOneBy(['code' => 'b2c_fr']),
            $indexPatterns,
            $settings,
            $mappings,
        );

        $this->assertEquals('gally_test__gally_b2c_fr_' . $identifier, $savedTemplate->getName());
        $this->assertEquals($indexPatterns, $savedTemplate->getIndexPatterns());
        $this->assertEquals(!empty($settings) ? ['index' => $settings] : [], $savedTemplate->getSettings());
        $this->assertEquals($mappings, $savedTemplate->getMappings());
    }

    public function createDataProvider(): iterable
    {
        yield [
            'dummy-1',
            ['gally_product_*'],
            ['number_of_shards' => 1],
            ['properties' => ['name' => ['type' => 'text']]],
        ];

        yield [
            'name' => 'dummy-2',
            'indexPatterns' => ['gally_category_*'],
        ];
    }

    /**
     * @depends testCreate
     *
     * @dataProvider findByNameDataProvider
     */
    public function testFindByName(string $templateId, ?array $expectedData): void
    {
        $foundTemplate = self::$repository->findByName($templateId);

        if (null === $expectedData) {
            $this->assertNull($foundTemplate);

            return;
        }

        $this->assertNotNull($foundTemplate);
        $this->assertEquals($expectedData['name'], $foundTemplate->getName());
        $this->assertEquals($expectedData['indexPatterns'], $foundTemplate->getIndexPatterns());
        $this->assertEquals($expectedData['entity'] ?? null, $foundTemplate->getEntityType());
        $this->assertEquals($expectedData['localizedCatalogCode'] ?? null, $foundTemplate->getLocalizedCatalog()?->getCode());
    }

    public function findByNameDataProvider(): iterable
    {
        yield [
            'gally_test__gally_b2c_fr_event',
            ['name' => 'gally_test__gally_b2c_fr_event', 'indexPatterns' => ['test-event-*'], 'entity' => 'event', 'localizedCatalogCode' => 'b2c_fr'],
        ];
        yield [
            'gally_test__gally_b2c_fr_dummy-1',
            ['name' => 'gally_test__gally_b2c_fr_dummy-1', 'indexPatterns' => ['gally_product_*'], 'entity' => 'dummy-1', 'localizedCatalogCode' => 'b2c_fr'],
        ];
        yield [
            'gally_test__gally_b2c_fr_dummy-2',
            ['name' => 'gally_test__gally_b2c_fr_dummy-2', 'indexPatterns' => ['gally_category_*'], 'entity' => 'dummy-2', 'localizedCatalogCode' => 'b2c_fr'],
        ];
        yield [
            'gally_test__gally_non_existent',
            null,
        ];
    }

    /**
     * @depends testFindByName
     */
    public function testUpdate(): void
    {
        $template = self::$repository->findByName('gally_test__gally_b2c_fr_dummy-1');
        $template->setSettings(['number_of_shards' => 2]);

        $template = self::$repository->save($template);

        $this->assertNotNull($template);
        $this->assertEquals('gally_test__gally_b2c_fr_dummy-1', $template->getName());
        $this->assertEquals(['index' => ['number_of_shards' => 2]], $template->getSettings());
    }

    /**
     * @depends testUpdate
     */
    public function testDeleteTemplate(): void
    {
        self::$repository->delete('gally_test__gally_b2c_fr_dummy-1');

        $deletedTemplate = self::$repository->findByName('gally_test__gally_b2c_fr_dummy-1');
        $this->assertNull($deletedTemplate);
    }

    /**
     * @depends testDeleteTemplate
     */
    public function testFindAll(): void
    {
        $templates = self::$repository->findAll();
        $this->assertCount(2, $templates);
    }

    private static function cleanupTestTemplates(): void
    {
        foreach (self::$testTemplates as $templateId) {
            try {
                self::$repository->delete($templateId);
            } catch (\Exception $e) {
                // Template doesn't exist, ignore
            }
        }
    }
}
