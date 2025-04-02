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

namespace Gally\Index\Tests\Unit;

use Doctrine\Persistence\ObjectManager;
use Gally\Index\Service\MappingManager;
use Gally\Metadata\Repository\MetadataRepository;
use Gally\Metadata\Service\MetadataManager;
use Gally\Test\AbstractTestCase;

class IndexManagerTest extends AbstractTestCase
{
    protected MetadataRepository $metadataRepository;
    protected MappingManager $mappingManager;
    protected MetadataManager $metadataManager;
    protected ObjectManager $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->metadataRepository = static::getContainer()->get(MetadataRepository::class);
        $this->mappingManager = static::getContainer()->get(MappingManager::class);
        $this->metadataManager = static::getContainer()->get(MetadataManager::class);
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->loadFixture([
            __DIR__ . '/../fixtures/catalogs.yaml',
            __DIR__ . '/../fixtures/source_field_option.yaml',
            __DIR__ . '/../fixtures/source_field.yaml',
            __DIR__ . '/../fixtures/metadata.yaml',
        ]);
    }

    /**
     * @dataProvider mappingDataProvider
     */
    public function testGetMapping(string $entity, array $expectedMapping): void
    {
        $metadata = $this->metadataRepository->findByEntity($entity);
        $this->entityManager->refresh($metadata); // Flush entity in order to avoid empty relations
        $actualMapping = $this->mappingManager->getMapping($metadata)->asArray();
        foreach ($expectedMapping['properties'] as $propertyName => $expectedProperty) {
            $this->assertEquals($expectedProperty, $actualMapping['properties'][$propertyName]);
        }
    }

    public function mappingDataProvider(): array
    {
        return [
            [
                'product',
                [
                    'properties' => [
                        'id' => [
                            'type' => 'text',
                            'analyzer' => 'keyword',
                            'norms' => false,
                            'fields' => [
                                'reference' => [
                                    'type' => 'text',
                                    'analyzer' => 'reference',
                                ],
                                'sortable' => [
                                    'type' => 'text',
                                    'analyzer' => 'sortable',
                                    'fielddata' => true,
                                ],
                            ],
                        ],
                        'sku' => [
                            'type' => 'text',
                            'analyzer' => 'keyword',
                            'norms' => false,
                        ],
                        'price' => [
                            'type' => 'nested',
                            'properties' => [
                                'original_price' => ['type' => 'double'],
                                'price' => ['type' => 'double'],
                                'is_discounted' => ['type' => 'boolean'],
                                'group_id' => ['type' => 'keyword'],
                            ],
                        ],
                        'stock' => [
                            'type' => 'nested',
                            'properties' => [
                                'status' => ['type' => 'integer'],
                                'qty' => ['type' => 'double'],
                            ],
                        ],
                        'name' => [
                            'type' => 'text',
                            'fields' => [
                                'whitespace' => [
                                    'type' => 'text',
                                    'analyzer' => 'whitespace',
                                ],
                                'shingle' => [
                                    'type' => 'text',
                                    'analyzer' => 'shingle',
                                ],
                                'standard_edge_ngram' => [
                                    'type' => 'text',
                                    'analyzer' => 'standard_edge_ngram',
                                    'search_analyzer' => 'standard',
                                ],
                            ],
                            'analyzer' => 'keyword',
                            'copy_to' => ['search', 'edge_ngram'],
                            'norms' => false,
                        ],
                        'brand' => [
                            'type' => 'nested',
                            'properties' => [
                                'value' => [
                                    'type' => 'keyword',
                                ],
                                'label' => [
                                    'type' => 'text',
                                    'analyzer' => 'keyword',
                                    'norms' => false,
                                ],
                            ],
                        ],
                        'search' => [
                            'type' => 'text',
                            'analyzer' => 'standard',
                            'fields' => [
                                'whitespace' => [
                                    'type' => 'text',
                                    'analyzer' => 'whitespace',
                                ],
                                'shingle' => [
                                    'type' => 'text',
                                    'analyzer' => 'shingle',
                                ],
                            ],
                        ],
                        'spelling' => [
                            'type' => 'text',
                            'analyzer' => 'standard',
                            'fields' => [
                                'whitespace' => [
                                    'type' => 'text',
                                    'analyzer' => 'whitespace',
                                ],
                                'shingle' => [
                                    'type' => 'text',
                                    'analyzer' => 'shingle',
                                ],
                                'phonetic' => [
                                    'type' => 'text',
                                    'analyzer' => 'phonetic',
                                ],
                            ],
                        ],
                        'children' => [
                            'type' => 'object',
                            'properties' => [
                                'sku' => [
                                    'type' => 'text',
                                    'analyzer' => 'keyword',
                                    'norms' => false,
                                ],
                                'name' => [
                                    'type' => 'text',
                                    'fields' => [
                                        'whitespace' => [
                                            'type' => 'text',
                                            'analyzer' => 'whitespace',
                                        ],
                                        'shingle' => [
                                            'type' => 'text',
                                            'analyzer' => 'shingle',
                                        ],
                                        'standard_edge_ngram' => [
                                            'type' => 'text',
                                            'analyzer' => 'standard_edge_ngram',
                                            'search_analyzer' => 'standard',
                                        ],
                                    ],
                                    'analyzer' => 'keyword',
                                    'copy_to' => ['search', 'edge_ngram'],
                                    'norms' => false,
                                ],
                                'id' => [
                                    'type' => 'text',
                                    'analyzer' => 'keyword',
                                    'norms' => false,
                                    'fields' => [
                                        'reference' => [
                                            'type' => 'text',
                                            'analyzer' => 'reference',
                                        ],
                                        'sortable' => [
                                            'type' => 'text',
                                            'analyzer' => 'sortable',
                                            'fielddata' => true,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'category',
                [
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'name' => [
                            'type' => 'text',
                            'analyzer' => 'keyword',
                            'norms' => false,
                        ],
                        'description' => [
                            'type' => 'text',
                            'analyzer' => 'keyword',
                            'copy_to' => ['search', 'spelling'],
                            'fields' => [
                                'standard' => [
                                    'type' => 'text',
                                    'analyzer' => 'standard',
                                ],
                            ],
                            'norms' => false,
                        ],
                        // No 'short_description' on purpose because no 'type' on source field.
                        'search' => [
                            'type' => 'text',
                            'analyzer' => 'standard',
                            'fields' => [
                                'whitespace' => [
                                    'type' => 'text',
                                    'analyzer' => 'whitespace',
                                ],
                                'shingle' => [
                                    'type' => 'text',
                                    'analyzer' => 'shingle',
                                ],
                            ],
                        ],
                        'spelling' => [
                            'type' => 'text',
                            'analyzer' => 'standard',
                            'fields' => [
                                'whitespace' => [
                                    'type' => 'text',
                                    'analyzer' => 'whitespace',
                                ],
                                'shingle' => [
                                    'type' => 'text',
                                    'analyzer' => 'shingle',
                                ],
                                'phonetic' => [
                                    'type' => 'text',
                                    'analyzer' => 'phonetic',
                                ],
                            ],
                        ],
                        'children' => [
                            'type' => 'object',
                            'properties' => [
                                'name' => [
                                    'type' => 'text',
                                    'analyzer' => 'keyword',
                                    'norms' => false,
                                ],
                                'description' => [
                                    'type' => 'text',
                                    'analyzer' => 'keyword',
                                    'copy_to' => ['search', 'spelling'],
                                    'fields' => [
                                        'standard' => [
                                            'type' => 'text',
                                            'analyzer' => 'standard',
                                        ],
                                    ],
                                    'norms' => false,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
