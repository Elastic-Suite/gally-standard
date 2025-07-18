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

namespace Gally\Search\Tests\Unit\Elasticsearch\Adapter\Common\Request\SortOrder;

use Gally\Configuration\Service\ConfigurationManager;
use Gally\Index\Entity\Index\Mapping\FieldInterface;
use Gally\Index\Entity\Index\MappingInterface;
use Gally\Index\Service\MetadataManager;
use Gally\Metadata\Repository\MetadataRepository;
use Gally\Search\Elasticsearch\Adapter\Common\Request\Query\Assembler as QueryAssembler;
use Gally\Search\Elasticsearch\Adapter\Common\Request\SortOrder\Assembler as SortAssembler;
use Gally\Search\Elasticsearch\Builder\Request\Query\Filter\FilterQueryBuilder;
use Gally\Search\Elasticsearch\Builder\Request\SortOrder\GeoDistance;
use Gally\Search\Elasticsearch\Builder\Request\SortOrder\Nested;
use Gally\Search\Elasticsearch\Builder\Request\SortOrder\Script;
use Gally\Search\Elasticsearch\Builder\Request\SortOrder\SortOrderBuilder;
use Gally\Search\Elasticsearch\Request\ContainerConfigurationInterface;
use Gally\Search\Elasticsearch\Request\QueryFactory;
use Gally\Search\Elasticsearch\Request\QueryInterface;
use Gally\Search\Elasticsearch\Request\SortOrderInterface;
use Gally\Test\AbstractTestCase;
use Psr\Log\LoggerInterface;

class AssemblerTest extends AbstractTestCase
{
    private static FilterQueryBuilder $filterQueryBuilder;
    private static ConfigurationManager $configurationManager;
    private static MetadataRepository $metadataRepository;
    private static MetadataManager $metadataManager;
    private static SortOrderBuilder $sortOrderBuilder;
    private static QueryAssembler $queryAssembler;
    private static SortAssembler $sortAssembler;
    private static LoggerInterface $logger;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        \assert(static::getContainer()->get(QueryFactory::class) instanceof QueryFactory);
        self::$filterQueryBuilder = static::getContainer()->get(FilterQueryBuilder::class);
        self::$configurationManager = static::getContainer()->get(ConfigurationManager::class);
        self::$logger = static::getContainer()->get(LoggerInterface::class);
        self::$sortOrderBuilder = new SortOrderBuilder(
            self::$filterQueryBuilder,
            self::$configurationManager,
            self::$logger,
        );
        self::$queryAssembler = static::getContainer()->get(QueryAssembler::class);
        self::$sortAssembler = new SortAssembler(self::$queryAssembler);

        self::loadFixture([
            __DIR__ . '/../../../../../../fixtures/source_field.yaml',
            __DIR__ . '/../../../../../../fixtures/metadata.yaml',
        ]);

        self::$metadataRepository = static::getContainer()->get(MetadataRepository::class);
        self::$metadataManager = static::getContainer()->get(MetadataManager::class);
    }

    /**
     * @dataProvider assembleNestedSortOrdersDataProvider
     *
     * @param string $entityType                  Entity type
     * @param array  $sortOrders                  Request level sort orders specifications
     * @param array  $expectedBuiltSortOrders     Expected built sort orders collection
     * @param array  $expectedAssembledSortOrders Expected assembled sort orders
     */
    public function testAssembleNestedSortOrders(
        string $entityType,
        array $sortOrders,
        array $expectedBuiltSortOrders,
        array $expectedAssembledSortOrders
    ): void {
        $metadata = self::$metadataRepository->findByEntity($entityType);
        $this->assertNotNull($metadata);
        $this->assertNotNull($metadata->getEntity());
        $mapping = self::$metadataManager->getMapping($metadata);
        $this->assertNotEmpty($mapping);

        $containerConfig = $this->getContainerConfiguration($mapping);
        $builtSortOrders = self::$sortOrderBuilder->buildSortOrders($containerConfig, $sortOrders);
        $expectedSortOrderNum = \count($expectedAssembledSortOrders);
        $this->assertCount($expectedSortOrderNum, $builtSortOrders);

        for ($i = 0; $i < $expectedSortOrderNum; ++$i) {
            $sortOrder = &$builtSortOrders[$i];
            $expectedBuiltSortOrder = &$expectedBuiltSortOrders[$i];
            $this->assertEquals($expectedBuiltSortOrder['type'], $sortOrder->getType());
            $this->assertEquals($expectedBuiltSortOrder['field'], $sortOrder->getField());
            $this->assertEquals($expectedBuiltSortOrder['direction'], $sortOrder->getDirection());
            if (SortOrderInterface::TYPE_SCRIPT === $sortOrder->getType()) {
                /** @var Script $sortOrder */
                $this->assertEquals($expectedBuiltSortOrder['script'], $sortOrder->getScript());
            }
            if (SortOrderInterface::TYPE_NESTED === $sortOrder->getType()) {
                /** @var Nested $sortOrder */
                $this->assertNotNull($sortOrder->getNestedPath());
                if (\array_key_exists('nestedFilter', $expectedBuiltSortOrder) && $expectedBuiltSortOrder['nestedFilter']) {
                    $this->assertInstanceOf(QueryInterface::class, $sortOrder->getNestedFilter());
                } else {
                    $this->assertNull($sortOrder->getNestedFilter());
                }
            }
        }

        $assembledSortOrders = self::$sortAssembler->assembleSortOrders($builtSortOrders);
        for ($i = 0; $i < $expectedSortOrderNum; ++$i) {
            $assembledSortOrder = &$assembledSortOrders[$i];
            $expectedAssembledSortOrder = &$expectedAssembledSortOrders[$i];
            $this->assertEquals($expectedAssembledSortOrder, $assembledSortOrder);
        }
    }

    public function assembleNestedSortOrdersDataProvider(): array
    {
        return [
            [
                'product_document',  // entity type.
                [], // sort order specifications.
                [   // expected built sort orders.
                    [
                        'type' => SortOrderInterface::TYPE_STANDARD,
                        'field' => SortOrderInterface::DEFAULT_SORT_FIELD,
                        'direction' => SortOrderInterface::SORT_DESC,
                    ],
                    [
                        'type' => SortOrderInterface::TYPE_STANDARD,
                        'field' => 'id.sortable',
                        'direction' => SortOrderInterface::SORT_DESC,
                    ],
                ],
                [   // expected assembled sort orders.
                    [
                        SortOrderInterface::DEFAULT_SORT_FIELD => [
                            'order' => SortOrderInterface::SORT_DESC,
                        ],
                    ],
                    [
                        'id.sortable' => [
                            'order' => SortOrderInterface::SORT_DESC,
                            'missing' => SortOrderInterface::MISSING_FIRST,
                            'unmapped_type' => FieldInterface::FIELD_TYPE_KEYWORD,
                        ],
                    ],
                ],
            ],
            [
                'product_document',  // entity type.
                [   // sort order specifications.
                    'price.price' => [
                        'field' => 'price.price',
                        'direction' => SortOrderInterface::SORT_ASC,
                    ],
                ],
                [   // expected built sort orders.
                    [
                        'type' => SortOrderInterface::TYPE_NESTED,
                        'field' => 'price.price',
                        'direction' => SortOrderInterface::SORT_ASC,
                        'nestedPath' => 'price',
                    ],
                    [
                        'type' => SortOrderInterface::TYPE_STANDARD,
                        'field' => SortOrderInterface::DEFAULT_SORT_FIELD,
                        'direction' => SortOrderInterface::SORT_DESC,
                    ],
                    [
                        'type' => SortOrderInterface::TYPE_STANDARD,
                        'field' => 'id.sortable',
                        'direction' => SortOrderInterface::SORT_DESC,
                    ],
                ],
                [   // expected assembled sort orders.
                    [
                        'price.price' => [
                            'order' => SortOrderInterface::SORT_ASC,
                            'missing' => SortOrderInterface::MISSING_LAST,
                            'unmapped_type' => 'keyword',
                            'nested' => ['path' => 'price'],
                            'mode' => SortOrderInterface::SCORE_MODE_MIN,
                        ],
                    ],
                    [
                        SortOrderInterface::DEFAULT_SORT_FIELD => [
                            'order' => SortOrderInterface::SORT_DESC,
                        ],
                    ],
                    [
                        'id.sortable' => [
                            'order' => SortOrderInterface::SORT_DESC,
                            'missing' => SortOrderInterface::MISSING_FIRST,
                            'unmapped_type' => FieldInterface::FIELD_TYPE_KEYWORD,
                        ],
                    ],
                ],
            ],
            [
                'product_document',  // entity type.
                [   // sort order specifications.
                    'price.price' => [
                        'field' => 'price.price',
                        'direction' => SortOrderInterface::SORT_DESC,
                    ],
                ],
                [   // expected built sort orders.
                    [
                        'type' => SortOrderInterface::TYPE_NESTED,
                        'field' => 'price.price',
                        'direction' => SortOrderInterface::SORT_DESC,
                        'nestedPath' => 'price',
                    ],
                    [
                        'type' => SortOrderInterface::TYPE_STANDARD,
                        'field' => SortOrderInterface::DEFAULT_SORT_FIELD,
                        'direction' => SortOrderInterface::SORT_ASC,
                    ],
                    [
                        'type' => SortOrderInterface::TYPE_STANDARD,
                        'field' => 'id.sortable',
                        'direction' => SortOrderInterface::SORT_ASC,
                    ],
                ],
                [
                    // expected assembled sort orders.
                    [
                        'price.price' => [
                            'order' => SortOrderInterface::SORT_DESC,
                            'missing' => SortOrderInterface::MISSING_FIRST,
                            'unmapped_type' => 'keyword',
                            'nested' => ['path' => 'price'],
                            'mode' => SortOrderInterface::SCORE_MODE_MIN,
                        ],
                    ],
                    [
                        SortOrderInterface::DEFAULT_SORT_FIELD => [
                            'order' => SortOrderInterface::SORT_ASC,
                        ],
                    ],
                    [
                        'id.sortable' => [
                            'order' => SortOrderInterface::SORT_ASC,
                            'missing' => SortOrderInterface::MISSING_LAST,
                            'unmapped_type' => FieldInterface::FIELD_TYPE_KEYWORD,
                        ],
                    ],
                ],
            ],
            [
                'product_document',  // entity type.
                [   // sort order specifications.
                    'price.price' => [
                        'field' => 'price.price',
                        'direction' => SortOrderInterface::SORT_DESC,
                        'nestedFilter' => ['price.group_id' => 0],
                    ],
                ],
                [   // expected built sort orders.
                    [
                        'type' => SortOrderInterface::TYPE_NESTED,
                        'field' => 'price.price',
                        'direction' => SortOrderInterface::SORT_DESC,
                        'nestedPath' => 'price',
                        'nestedFilter' => true,
                    ],
                    [
                        'type' => SortOrderInterface::TYPE_STANDARD,
                        'field' => SortOrderInterface::DEFAULT_SORT_FIELD,
                        'direction' => SortOrderInterface::SORT_ASC,
                    ],
                    [
                        'type' => SortOrderInterface::TYPE_STANDARD,
                        'field' => 'id.sortable',
                        'direction' => SortOrderInterface::SORT_ASC,
                    ],
                ],
                [   // expected assembled sort orders.
                    [
                        'price.price' => [
                            'order' => SortOrderInterface::SORT_DESC,
                            'missing' => SortOrderInterface::MISSING_FIRST,
                            'nested' => [
                                'path' => 'price',
                                'filter' => [
                                    'terms' => [
                                        'price.group_id' => [0],
                                        'boost' => 1,
                                    ],
                                ],
                            ],
                            'mode' => SortOrderInterface::SCORE_MODE_MIN,
                            'unmapped_type' => 'keyword',
                        ],
                    ],
                    [
                        SortOrderInterface::DEFAULT_SORT_FIELD => [
                            'order' => SortOrderInterface::SORT_ASC,
                        ],
                    ],
                    [
                        'id.sortable' => [
                            'order' => SortOrderInterface::SORT_ASC,
                            'missing' => SortOrderInterface::MISSING_LAST,
                            'unmapped_type' => FieldInterface::FIELD_TYPE_KEYWORD,
                        ],
                    ],
                ],
            ],
            [
                'product_document',  // entity type.
                [   // sort order specifications.
                    Script::SCRIPT_FIELD => [
                        'field' => Script::SCRIPT_FIELD,
                        'direction' => SortOrderInterface::SORT_DESC,
                        'scriptType' => 'number',
                        'lang' => 'painless',
                        'source' => "doc['popularity'].value * params.factor",
                        'params' => ['factor' => 1.1],
                    ],
                ],
                [   // expected built sort orders.
                    [
                        'type' => SortOrderInterface::TYPE_SCRIPT,
                        'field' => Script::SCRIPT_FIELD,
                        'direction' => SortOrderInterface::SORT_DESC,
                        'scriptType' => 'number',
                        'script' => [
                            'lang' => 'painless',
                            'source' => "doc['popularity'].value * params.factor",
                            'params' => ['factor' => 1.1],
                        ],
                    ],
                    [
                        'type' => SortOrderInterface::TYPE_STANDARD,
                        'field' => SortOrderInterface::DEFAULT_SORT_FIELD,
                        'direction' => SortOrderInterface::SORT_ASC,
                    ],
                    [
                        'type' => SortOrderInterface::TYPE_STANDARD,
                        'field' => 'id.sortable',
                        'direction' => SortOrderInterface::SORT_ASC,
                    ],
                ],
                [
                    [
                        Script::SCRIPT_FIELD => [
                            'order' => SortOrderInterface::SORT_DESC,
                            'type' => 'number',
                            'script' => [
                                'lang' => 'painless',
                                'source' => "doc['popularity'].value * params.factor",
                                'params' => ['factor' => 1.1],
                            ],
                        ],
                    ],
                    [
                        SortOrderInterface::DEFAULT_SORT_FIELD => [
                            'order' => SortOrderInterface::SORT_ASC,
                        ],
                    ],
                    [
                        'id.sortable' => [
                            'order' => SortOrderInterface::SORT_ASC,
                            'missing' => SortOrderInterface::MISSING_LAST,
                            'unmapped_type' => FieldInterface::FIELD_TYPE_KEYWORD,
                        ],
                    ],
                ],
                [
                    'product_document',  // entity type.
                    [   // sort order specifications.
                        // Ugly hack that allows dynamic product positioning.
                        Script::SCRIPT_FIELD => [
                            'field' => Script::SCRIPT_FIELD,
                            'direction' => [
                                'lang' => 'painless',
                                'scriptType' => 'number',
                                'source' => "if(params.scores.containsKey(doc['_id'].value)) { return params.scores[doc['_id'].value];} return 922337203685477600L",
                                'params' => ['scores' => [127 => 1, 849 => 2, 327 => 3]],
                                'direction' => SortOrderInterface::SORT_ASC,
                            ],
                        ],
                    ],
                    [   // expected built sort orders.
                        [
                            'type' => SortOrderInterface::TYPE_SCRIPT,
                            'field' => Script::SCRIPT_FIELD,
                            'direction' => SortOrderInterface::SORT_ASC,
                            'scriptType' => 'number',
                            'script' => [
                                'lang' => 'painless',
                                'source' => "if(params.scores.containsKey(doc['_id'].value)) { return params.scores[doc['_id'].value];} return 922337203685477600L",
                                'params' => ['scores' => [127 => 1, 849 => 2, 327 => 3]],
                            ],
                        ],
                        [
                            'type' => SortOrderInterface::TYPE_STANDARD,
                            'field' => SortOrderInterface::DEFAULT_SORT_FIELD,
                            'direction' => SortOrderInterface::SORT_DESC,
                        ],
                        [
                            'type' => SortOrderInterface::TYPE_STANDARD,
                            'field' => 'id.sortable',
                            'direction' => SortOrderInterface::SORT_DESC,
                        ],
                    ],
                    [
                        [
                            Script::SCRIPT_FIELD => [
                                'order' => SortOrderInterface::SORT_ASC,
                                'type' => 'number',
                                'script' => [
                                    'lang' => 'painless',
                                    'source' => "if(params.scores.containsKey(doc['_id'].value)) { return params.scores[doc['_id'].value];} return 922337203685477600L",
                                    'params' => ['scores' => [127 => 1, 849 => 2, 327 => 3]],
                                ],
                            ],
                        ],
                        [
                            SortOrderInterface::DEFAULT_SORT_FIELD => [
                                'order' => SortOrderInterface::SORT_DESC,
                            ],
                        ],
                        [
                            'id.sortable' => [
                                'order' => SortOrderInterface::SORT_DESC,
                                'missing' => SortOrderInterface::MISSING_FIRST,
                                'unmapped_type' => 'keyword',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'product_document',  // entity type.
                [   // sort order specifications.
                    'manufacture_location' => [
                        'field' => 'manufacture_location',
                        'direction' => SortOrderInterface::SORT_DESC,
                        'referenceLocation' => '-01,23 45,67',
                    ],
                ],
                [   // expected built sort orders.
                    [
                        'type' => SortOrderInterface::TYPE_DISTANCE,
                        'field' => 'manufacture_location',
                        'direction' => SortOrderInterface::SORT_DESC,
                        'referenceLocation' => '-01,23 45,67',
                        'unit' => 'km',
                        'mode' => 'min',
                        'distanceType' => 'arc',
                        'ignoreUnmapped' => false,
                        'name' => null,
                    ],
                    [
                        'type' => SortOrderInterface::TYPE_STANDARD,
                        'field' => SortOrderInterface::DEFAULT_SORT_FIELD,
                        'direction' => SortOrderInterface::SORT_ASC,
                    ],
                    [
                        'type' => SortOrderInterface::TYPE_STANDARD,
                        'field' => 'id.sortable',
                        'direction' => SortOrderInterface::SORT_ASC,
                    ],
                ],
                [
                    [
                        GeoDistance::GEO_DISTANCE_FIELD => [
                            'order' => SortOrderInterface::SORT_DESC,
                            'manufacture_location' => '-01,23 45,67',
                            'unit' => 'km',
                            'mode' => 'min',
                            'distance_type' => 'arc',
                            'ignore_unmapped' => false,
                        ],
                    ],
                    [
                        SortOrderInterface::DEFAULT_SORT_FIELD => [
                            'order' => SortOrderInterface::SORT_ASC,
                        ],
                    ],
                    [
                        'id.sortable' => [
                            'order' => SortOrderInterface::SORT_ASC,
                            'missing' => SortOrderInterface::MISSING_LAST,
                            'unmapped_type' => FieldInterface::FIELD_TYPE_KEYWORD,
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function getContainerConfiguration(MappingInterface $mapping): ContainerConfigurationInterface
    {
        $containerConfig = $this->getMockBuilder(ContainerConfigurationInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $containerConfig->method('getMapping')->willReturn($mapping);

        return $containerConfig;
    }
}
