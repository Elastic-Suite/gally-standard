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
use Gally\Index\Entity\IndexTemplate;
use Gally\Index\Repository\IndexTemplate\IndexTemplateRepositoryInterface;
use Gally\Metadata\Repository\MetadataRepository;
use Gally\Test\AbstractTestCase;

class IndexTemplateRepositoryTest extends AbstractTestCase
{
    private static LocalizedCatalogRepository $localizedCatalogRepository;
    private static MetadataRepository $metadataRepository;

    private static array $testTemplates = [
        'gally_test__gally_b2c_fr_event',
        'gally_test__gally_b2c_fr_dummy-1',
        'gally_test__gally_b2c_en_dummy-2',
    ];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        \assert(static::getContainer()->get(LocalizedCatalogRepository::class) instanceof LocalizedCatalogRepository);
        self::$localizedCatalogRepository = static::getContainer()->get(LocalizedCatalogRepository::class);
        \assert(static::getContainer()->get(MetadataRepository::class) instanceof MetadataRepository);
        self::$metadataRepository = static::getContainer()->get(MetadataRepository::class);

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

    /**
     * @dataProvider createForEntityDataProvider
     */
    public function testCreateForEntity(
        string $entityCode,
        string $localizedCatalogCode,
        array $expectedData,
    ): void {
        $repository = static::getContainer()->get(IndexTemplateRepositoryInterface::class);

        $this->validateTemplate(
            $expectedData,
            $repository->createForEntity(
                self::$metadataRepository->findByEntity($entityCode),
                self::$localizedCatalogRepository->findOneBy(['code' => $localizedCatalogCode]),
            )
        );
    }

    public function createForEntityDataProvider(): iterable
    {
        yield 'event template' => [
            'tracking_event',
            'b2c_fr',
            [
                'id' => 'gally_test__gally_b2c_fr_tracking_event',
                'name' => 'tracking_event',
                'indexPatterns' => ['gally_test__gally_b2c_fr_tracking_event'],
                'entityType' => 'tracking_event',
                'localizedCatalogCode' => 'b2c_fr',
                'aliases' => ['.entity_tracking_event', '.catalog_1'],
                'mappings' => [
                    'id' => ['type' => 'text', 'norms' => false, 'analyzer' => 'keyword'],
                    '@timestamp' => ['type' => 'date', 'format' => 'yyyy-MM-dd HH:mm:ss||yyyy-MM-dd'],
                    'event_type' => ['type' => 'text', 'norms' => false, 'analyzer' => 'keyword'],
                ],
                'settings' => [
                    'max_result_window' => '100000',
                    'number_of_replicas' => '0',
                    'number_of_shards' => '1',
                ],
                'isDataStream' => true,
            ],
        ];

        yield 'product template' => [
            'product',
            'b2c_en',
            [
                'id' => 'gally_test__gally_b2c_en_product',
                'name' => 'product',
                'indexPatterns' => ['gally_test__gally_b2c_en_product'],
                'entityType' => 'product',
                'localizedCatalogCode' => 'b2c_en',
                'aliases' => ['.entity_product', '.catalog_2'],
                'mappings' => [
                    'id' => [
                        'type' => 'text',
                        'norms' => false,
                        'analyzer' => 'keyword',
                        'fields' => [
                            'reference' => [
                                'analyzer' => 'reference',
                                'type' => 'text',
                            ],
                            'sortable' => [
                                'fielddata' => true,
                                'analyzer' => 'sortable',
                                'type' => 'text',
                            ],
                        ],
                    ],
                    'name' => [
                        'type' => 'text',
                        'copy_to' => ['search', 'edge_ngram'],
                        'norms' => false,
                        'analyzer' => 'keyword',
                        'fields' => [
                            'shingle' => [
                                'analyzer' => 'shingle',
                                'type' => 'text',
                            ],
                            'whitespace' => [
                                'analyzer' => 'whitespace',
                                'type' => 'text',
                            ],
                            'standard_edge_ngram' => [
                                'search_analyzer' => 'standard',
                                'analyzer' => 'standard_edge_ngram',
                                'type' => 'text',
                            ],
                        ],
                    ],
                ],
                'settings' => [
                    'max_result_window' => '100000',
                    'number_of_replicas' => '0',
                    'number_of_shards' => '1',
                ],
            ],
        ];
    }

    /**
     * @depends testCreateForEntity
     *
     * @dataProvider createDataProvider
     */
    public function testCreate(array $data, string $localizedCatalogCode): void
    {
        $repository = static::getContainer()->get(IndexTemplateRepositoryInterface::class);
        $localizedCatalog = self::$localizedCatalogRepository->findOneBy(['code' => $localizedCatalogCode]);

        $this->validateTemplate(
            $data,
            $repository->create(
                indexIdentifier: $data['identifier'],
                localizedCatalog: $localizedCatalog,
                indexPatterns: $data['indexPatterns'],
                indexSettings: $data['settings'] ?? [],
                mappings: $data['mappings'] ?? [],
                isDataStream: $data['isDataStream'] ?? false,
            )
        );
    }

    public function createDataProvider(): iterable
    {
        yield 'dummy-1 template' => [
            [
                'identifier' => 'dummy-1',
                'id' => 'gally_test__gally_b2c_fr_dummy-1',
                'name' => 'dummy-1',
                'indexPatterns' => ['gally_product_*'],
                'settings' => ['number_of_shards' => 1],
                'localizedCatalogCode' => 'b2c_fr',
            ],
            'b2c_fr',
        ];

        yield 'dummy-2 template' => [
            [
                'identifier' => 'dummy-2',
                'id' => 'gally_test__gally_b2c_en_dummy-2',
                'name' => 'dummy-2',
                'indexPatterns' => ['gally_category_*'],
                'localizedCatalogCode' => 'b2c_en',
                'isDataStream' => true,
            ],
            'b2c_en',
        ];
    }

    /**
     * @depends testCreate
     *
     * @dataProvider findByMetadataDataProvider
     */
    public function testFindByMetadata(string $entityCode, string $localizedCatalogCode, ?array $expectedData): void
    {
        $repository = static::getContainer()->get(IndexTemplateRepositoryInterface::class);
        $foundTemplate = $repository->findByMetadata(
            self::$metadataRepository->findByEntity($entityCode),
            self::$localizedCatalogRepository->findOneBy(['code' => $localizedCatalogCode]),
        );

        if (null === $expectedData) {
            $this->assertNull($foundTemplate);

            return;
        }

        $this->assertNotNull($foundTemplate);
        $this->validateTemplate($expectedData, $foundTemplate);
    }

    public function findByMetadataDataProvider(): iterable
    {
        yield 'tracking_event template found' => [
            'tracking_event',
            'b2c_fr',
            [
                'id' => 'gally_test__gally_b2c_fr_tracking_event',
                'name' => 'tracking_event',
                'indexPatterns' => ['gally_test__gally_b2c_fr_tracking_event'],
                'entityType' => 'tracking_event',
                'localizedCatalogCode' => 'b2c_fr',
                'isDataStream' => true,
            ],
        ];

        yield 'category template not found (not created)' => [
            'category',
            'b2c_fr',
            null,
        ];

        yield 'tracking_event template wrong catalog' => [
            'tracking_event',
            'b2c_en',
            null,
        ];
    }

    /**
     * @depends testFindByMetadata
     *
     * @dataProvider findByNameDataProvider
     */
    public function testFindByName(string $name, string $localizedCatalogCode, ?array $expectedData): void
    {
        $repository = static::getContainer()->get(IndexTemplateRepositoryInterface::class);
        $foundTemplate = $repository->findByName(
            $name,
            self::$localizedCatalogRepository->findOneBy(['code' => $localizedCatalogCode]),
        );

        if (null === $expectedData) {
            $this->assertNull($foundTemplate);

            return;
        }

        $this->assertNotNull($foundTemplate);
        $this->validateTemplate($expectedData, $foundTemplate);
    }

    public function findByNameDataProvider(): iterable
    {
        yield 'tracking_event template' => [
            'tracking_event',
            'b2c_fr',
            [
                'id' => 'gally_test__gally_b2c_fr_tracking_event',
                'name' => 'tracking_event',
                'indexPatterns' => ['gally_test__gally_b2c_fr_tracking_event'],
                'entityType' => 'tracking_event',
                'localizedCatalogCode' => 'b2c_fr',
                'isDataStream' => true,
            ],
        ];

        yield 'dummy-1 template' => [
            'dummy-1',
            'b2c_fr',
            [
                'id' => 'gally_test__gally_b2c_fr_dummy-1',
                'name' => 'dummy-1',
                'indexPatterns' => ['gally_product_*'],
                'localizedCatalogCode' => 'b2c_fr',
                'isDataStream' => false,
            ],
        ];

        yield 'dummy-2 template' => [
            'dummy-2',
            'b2c_en',
            [
                'id' => 'gally_test__gally_b2c_en_dummy-2',
                'name' => 'dummy-2',
                'indexPatterns' => ['gally_category_*'],
                'localizedCatalogCode' => 'b2c_en',
                'isDataStream' => true,
            ],
        ];

        yield 'non existent' => [
            'non_existent',
            'b2c_fr',
            null,
        ];
    }

    /**
     * @depends testFindByName
     *
     * @dataProvider findByIdDataProvider
     */
    public function testFindById(string $id, ?array $expectedData): void
    {
        $repository = static::getContainer()->get(IndexTemplateRepositoryInterface::class);
        $foundTemplate = $repository->findById($id);

        if (null === $expectedData) {
            $this->assertNull($foundTemplate);

            return;
        }

        $this->assertNotNull($foundTemplate);
        $this->validateTemplate($expectedData, $foundTemplate);
    }

    public function findByIdDataProvider(): iterable
    {
        yield 'tracking_event template by id' => [
            'gally_test__gally_b2c_fr_tracking_event',
            [
                'id' => 'gally_test__gally_b2c_fr_tracking_event',
                'name' => 'tracking_event',
                'indexPatterns' => ['gally_test__gally_b2c_fr_tracking_event'],
                'entityType' => 'tracking_event',
                'localizedCatalogCode' => 'b2c_fr',
                'isDataStream' => true,
            ],
        ];

        yield 'dummy-1 template by id' => [
            'gally_test__gally_b2c_fr_dummy-1',
            [
                'id' => 'gally_test__gally_b2c_fr_dummy-1',
                'name' => 'dummy-1',
                'indexPatterns' => ['gally_product_*'],
                'localizedCatalogCode' => 'b2c_fr',
                'isDataStream' => false,
            ],
        ];

        yield 'dummy-2 template by id' => [
            'gally_test__gally_b2c_en_dummy-2',
            [
                'id' => 'gally_test__gally_b2c_en_dummy-2',
                'name' => 'dummy-2',
                'indexPatterns' => ['gally_category_*'],
                'localizedCatalogCode' => 'b2c_en',
                'isDataStream' => true,
            ],
        ];

        yield 'non existent id' => [
            'gally_test__non_existent_template',
            null,
        ];
    }

    /**
     * @depends testFindById
     */
    public function testUpdate(): void
    {
        $repository = static::getContainer()->get(IndexTemplateRepositoryInterface::class);
        $template = $repository->findByName(
            'dummy-1',
            self::$localizedCatalogRepository->findOneBy(['code' => 'b2c_fr']),
        );

        $this->assertNotNull($template);
        $template->setSettings(['number_of_shards' => 2]);

        $updatedTemplate = $repository->update($template);

        $this->validateTemplate(
            [
                'id' => 'gally_test__gally_b2c_fr_dummy-1',
                'name' => 'dummy-1',
                'indexPatterns' => ['gally_product_*'],
                'settings' => ['number_of_shards' => 2],
                'localizedCatalogCode' => 'b2c_fr',
            ],
            $updatedTemplate
        );
    }

    /**
     * @depends testUpdate
     */
    public function testDelete(): void
    {
        $repository = static::getContainer()->get(IndexTemplateRepositoryInterface::class);
        $repository->delete('gally_test__gally_b2c_fr_dummy-1');

        $deletedTemplate = $repository->findByName(
            'dummy-1',
            self::$localizedCatalogRepository->findOneBy(['code' => 'b2c_fr']),
        );
        $this->assertNull($deletedTemplate);
    }

    /**
     * @depends testDelete
     */
    public function testFindAll(): void
    {
        $repository = static::getContainer()->get(IndexTemplateRepositoryInterface::class);
        $templates = $repository->findAll();

        // We expect at least tracking_event (fr) and dummy-2 (en) to remain
        $this->assertGreaterThanOrEqual(2, \count($templates));

        $ids = array_map(fn ($t) => $t->getId(), $templates);
        $this->assertContains('gally_test__gally_b2c_fr_tracking_event', $ids);
        $this->assertContains('gally_test__gally_b2c_en_dummy-2', $ids);
    }

    private static function cleanupTestTemplates(): void
    {
        $repository = static::getContainer()->get(IndexTemplateRepositoryInterface::class);
        foreach (self::$testTemplates as $templateId) {
            try {
                $repository->delete($templateId);
            } catch (\Exception $e) {
                // Template doesn't exist, ignore
            }
        }
    }

    private function validateTemplate(array $expectedData, IndexTemplate $template): void
    {
        $this->assertEquals($expectedData['id'], $template->getId());
        $this->assertEquals($expectedData['name'], $template->getName());
        $this->assertEquals($expectedData['indexPatterns'], $template->getIndexPatterns());
        $this->assertEquals($expectedData['localizedCatalogCode'], $template->getLocalizedCatalog()?->getCode());
        $this->assertEquals($expectedData['isDataStream'] ?? false, $template->isDataStreamTemplate());

        foreach ($expectedData['mappings'] ?? [] as $code => $config) {
            $this->assertArrayHasKey('properties', $template->getMappings());
            $this->assertArrayHasKey($code, $template->getMappings()['properties']);
            $this->assertEquals($config, $template->getMappings()['properties'][$code]);
        }

        foreach ($expectedData['settings'] ?? [] as $code => $config) {
            $this->assertArrayHasKey('index', $template->getSettings());
            $this->assertArrayHasKey($code, $template->getSettings()['index']);
            $this->assertEquals($config, $template->getSettings()['index'][$code]);
        }

        foreach ($expectedData['aliases'] ?? [] as $alias) {
            $this->assertContains($alias, $template->getAliases());
        }
    }
}
