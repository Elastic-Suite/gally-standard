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

namespace Gally\Search\Tests\Unit\Repository;

use Gally\Metadata\Repository\MetadataRepository;
use Gally\Search\Entity\IngestPipeline;
use Gally\Search\Repository\Ingest\PipelineRepository;
use Gally\Test\AbstractTestCase;
use OpenSearch\Common\Exceptions\BadRequest400Exception;

class IngestPipelineRepositoryTest extends AbstractTestCase
{
    public function testCreateInvalid(): void
    {
        $this->expectException(BadRequest400Exception::class);
        $ingestPipelineRepository = static::getContainer()->get(PipelineRepository::class);
        $ingestPipelineRepository->create(
            'test-ingest-pipeline-0',
            'Description pipeline 0',
            [
                ['fakeProcessor' => ['data' => 'value']],
            ]
        );
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(
        string $name,
        string $description,
        array $processors = [],
    ): void {
        $ingestPipelineRepository = static::getContainer()->get(PipelineRepository::class);
        $pipeline = $ingestPipelineRepository->create($name, $description, $processors);
        $this->assertEquals($name, $pipeline->getName());
        $this->assertEquals($description, $pipeline->getDescription());
        $this->assertEquals($processors, $pipeline->getProcessors());
    }

    protected function createDataProvider(): iterable
    {
        yield [
            'test-ingest-pipeline-1',
            'Description pipeline 1',
        ];
        yield [
            'test-ingest-pipeline-2',
            'Description pipeline 2',
            [
                [
                    'set' => [
                        'field' => 'text_embedding',
                        'value' => [''],
                    ],
                ],
            ],
        ];
        yield [
            'test-ingest-pipeline-3',
            'Description pipeline 3',
            [
                [
                    'set' => [
                        'field' => 'text_embedding',
                        'value' => [''],
                    ],
                ],
                [
                    'foreach' => [
                        'field' => 'name',
                        'ignore_missing' => true,
                        'if' => "(ctx['name'] instanceof List)",
                        'processor' => [
                            'append' => [
                                'field' => 'new_field',
                                'value' => '{{{_ingest._value}}}',
                            ],
                        ],
                    ],
                ],
                [
                    'append' => [
                        'field' => 'new_field',
                        'if' => "(ctx['name'] instanceof String)",
                        'value' => \sprintf('Le produit se nomme : %s', '{{{name}}}'),
                    ],
                ],
                [
                    'join' => [
                        'field' => 'new_field',
                        'separator' => ' ',
                    ],
                ],
            ],
        ];
    }

    /**
     * @depends testCreate
     *
     * @dataProvider getDataProvider
     */
    public function testGet(
        string $name,
        array $expectedProcessors
    ): void {
        $ingestPipelineRepository = static::getContainer()->get(PipelineRepository::class);
        $pipeline = $ingestPipelineRepository->get($name);
        $this->assertEquals($name, $pipeline->getName());
        $this->assertEquals($expectedProcessors, $pipeline->getProcessors());
    }

    protected function getDataProvider(): iterable
    {
        yield [
            'test-ingest-pipeline-1',
            [],
        ];
        yield [
            'test-ingest-pipeline-2',
            [
                [
                    'set' => [
                        'field' => 'text_embedding',
                        'value' => [''],
                    ],
                ],
            ],
        ];
        yield [
            'test-ingest-pipeline-3',
            [
                [
                    'set' => [
                        'field' => 'text_embedding',
                        'value' => [''],
                    ],
                ],
                [
                    'foreach' => [
                        'field' => 'name',
                        'ignore_missing' => true,
                        'if' => "(ctx['name'] instanceof List)",
                        'processor' => [
                            'append' => [
                                'field' => 'new_field',
                                'value' => '{{{_ingest._value}}}',
                            ],
                        ],
                    ],
                ],
                [
                    'append' => [
                        'field' => 'new_field',
                        'if' => "(ctx['name'] instanceof String)",
                        'value' => \sprintf('Le produit se nomme : %s', '{{{name}}}'),
                    ],
                ],
                [
                    'join' => [
                        'field' => 'new_field',
                        'separator' => ' ',
                    ],
                ],
            ],
        ];
    }

    public function testGetError(): void
    {
        $ingestPipelineRepository = static::getContainer()->get(PipelineRepository::class);
        $this->assertNull($ingestPipelineRepository->get('fake-pipeline'));
    }

    public function testCreateByMetadata(): void
    {
        static::loadFixture(
            [
                __DIR__ . '/../../fixtures/source_field.yaml',
                __DIR__ . '/../../fixtures/metadata.yaml',
            ]
        );

        $metadataRepository = static::getContainer()->get(MetadataRepository::class);
        $ingestPipelineRepository = static::getContainer()->get(PipelineRepository::class);

        $expectedPipeline = new IngestPipeline(
            'test-gally-llm-pipeline-category',
            'test-gally-llm-pipeline-category',
            [
                'set' => [
                    'field' => 'dummy',
                    'value' => ['test'],
                ],
            ]
        );
        $actualPipeline = $ingestPipelineRepository->createByMetadata($metadataRepository->findByEntity('category'));

        $this->assertEquals($expectedPipeline->getName(), $actualPipeline->getName());
        $this->assertEquals($expectedPipeline->getDescription(), $actualPipeline->getDescription());
        $this->assertGreaterThanOrEqual(\count($expectedPipeline->getProcessors()), \count($actualPipeline->getProcessors()));

        $expectedPipeline = new IngestPipeline(
            'test-gally-llm-pipeline-product_document',
            'test-gally-llm-pipeline-product_document',
            []
        );
        $actualPipeline = $ingestPipelineRepository->createByMetadata($metadataRepository->findByEntity('product_document'));
        // If pipeline doesn't have any processor it will be not created.
        if ($actualPipeline) {
            $this->assertEquals($expectedPipeline->getName(), $actualPipeline->getName());
            $this->assertEquals($expectedPipeline->getDescription(), $actualPipeline->getDescription());
            $this->assertGreaterThanOrEqual(\count($expectedPipeline->getProcessors()), \count($actualPipeline->getProcessors()));
        }
    }
}
