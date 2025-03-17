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

namespace Gally\Search\Tests\Unit\Elasticsearch\Builder\Request\SortOrder;

use Gally\Index\Entity\Index\MappingInterface;
use Gally\Index\Service\MetadataManager;
use Gally\Metadata\Repository\MetadataRepository;
use Gally\Search\Elasticsearch\Builder\Request\Query\Filter\FilterQueryBuilder;
use Gally\Search\Elasticsearch\Builder\Request\SortOrder\Nested;
use Gally\Search\Elasticsearch\Builder\Request\SortOrder\Script;
use Gally\Search\Elasticsearch\Builder\Request\SortOrder\SortOrderBuilder;
use Gally\Search\Elasticsearch\Request\ContainerConfigurationInterface;
use Gally\Search\Elasticsearch\Request\QueryFactory;
use Gally\Search\Elasticsearch\Request\QueryInterface;
use Gally\Search\Elasticsearch\Request\SortOrderInterface;
use Gally\Test\AbstractTestCase;
use Psr\Log\LoggerInterface;

class SortOrderBuilderTest extends AbstractTestCase
{
    private static FilterQueryBuilder $filterQueryBuilder;

    private static MetadataRepository $metadataRepository;

    private static MetadataManager $metadataManager;

    private static SortOrderBuilder $sortOrderBuilder;

    private static LoggerInterface $logger;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        \assert(static::getContainer()->get(QueryFactory::class) instanceof QueryFactory);
        self::$filterQueryBuilder = static::getContainer()->get(FilterQueryBuilder::class);
        self::$logger = static::getContainer()->get(LoggerInterface::class);
        self::$sortOrderBuilder = new SortOrderBuilder(
            self::$filterQueryBuilder,
            self::$logger,
            static::getContainer()->getParameter('gally.search_settings')
        );

        self::$metadataRepository = static::getContainer()->get(MetadataRepository::class);
        self::$metadataManager = static::getContainer()->get(MetadataManager::class);
        self::loadFixture([
            __DIR__ . '/../../../../../fixtures/catalogs.yaml',
            __DIR__ . '/../../../../../fixtures/source_field.yaml',
            __DIR__ . '/../../../../../fixtures/metadata.yaml',
        ]);
    }

    public function testInstantiate(): void
    {
        $reflector = new \ReflectionClass(SortOrderBuilder::class);
        $filterQueryBuilderProperty = $reflector->getProperty('queryBuilder');

        $sortOrderBuilder = new SortOrderBuilder(
            self::$filterQueryBuilder,
            self::$logger,
            static::getContainer()->getParameter('gally.search_settings')
        );
        $this->assertEquals($filterQueryBuilderProperty->getValue($sortOrderBuilder), self::$filterQueryBuilder);
    }

    /**
     * @dataProvider buildSortOrdersDataProvider
     *
     * @param string $entityType                  Entity type
     * @param array  $sortOrders                  Array of sort order specifications to build
     * @param array  $expectedSortOrderCollection Expected built sort orders
     */
    public function testBuildSortOrders(
        string $entityType,
        array $sortOrders,
        array $expectedSortOrderCollection
    ): void {
        $metadata = self::$metadataRepository->findByEntity($entityType);
        $this->assertNotNull($metadata);
        $this->assertNotNull($metadata->getEntity());
        $mapping = self::$metadataManager->getMapping($metadata);
        $this->assertNotEmpty($mapping);

        $containerConfig = $this->getContainerConfiguration($mapping);
        $sortOrderCollection = self::$sortOrderBuilder->buildSortOrders($containerConfig, $sortOrders);
        $expectedSortOrderNum = \count($expectedSortOrderCollection);
        $this->assertCount($expectedSortOrderNum, $sortOrderCollection);
        for ($i = 0; $i < $expectedSortOrderNum; ++$i) {
            $sortOrder = &$sortOrderCollection[$i];
            $expectedSortOrder = &$expectedSortOrderCollection[$i];
            $this->assertEquals($expectedSortOrder['type'], $sortOrder->getType());
            $this->assertEquals($expectedSortOrder['field'], $sortOrder->getField());
            $this->assertEquals($expectedSortOrder['direction'], $sortOrder->getDirection());
            if (SortOrderInterface::TYPE_SCRIPT === $sortOrder->getType()) {
                /** @var Script $sortOrder */
                $this->assertEquals($expectedSortOrder['script'], $sortOrder->getScript());
            }
            if (SortOrderInterface::TYPE_NESTED === $sortOrder->getType()) {
                /** @var Nested $sortOrder */
                $this->assertNotNull($sortOrder->getNestedPath());
                if (\array_key_exists('nestedFilter', $expectedSortOrder) && $expectedSortOrder['nestedFilter']) {
                    $this->assertInstanceOf(QueryInterface::class, $sortOrder->getNestedFilter());
                } else {
                    $this->assertNull($sortOrder->getNestedFilter());
                }
            }
        }
    }

    protected function buildSortOrdersDataProvider(): array
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
            ],
            [
                'product_document',  // entity type.
                [   // sort order specifications.
                    'price.price' => [
                        'field' => 'price.price',
                        'direction' => SortOrderInterface::SORT_ASC,
                        'nestedFilter' => ['price.group_id' => 0],
                    ],
                ],
                [   // expected built sort orders.
                    [
                        'type' => SortOrderInterface::TYPE_NESTED,
                        'field' => 'price.price',
                        'direction' => SortOrderInterface::SORT_ASC,
                        'nestedPath' => 'price',
                        'nestedFilter' => true,
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
            ],
            [
                'product_document',  // entity type.
                [   // sort order specifications.
                    // Geo distance sort
                    'manufacture_location' => [
                        'field' => 'manufacture_location',
                        'referenceLocation' => '12,3456 -65,4321',
                        'direction' => SortOrderInterface::SORT_DESC,
                    ],
                ],
                [   // expected built sort orders.
                    [
                        'type' => SortOrderInterface::TYPE_DISTANCE,
                        'field' => 'manufacture_location',
                        'direction' => SortOrderInterface::SORT_DESC,
                        'referenceLocation' => '12,3456 -65,4321',
                        'unit' => 'km',
                        'mode' => 'min',
                        'distanceType' => 'arc',
                        'ignoreUnmapped' => false,
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
            ],
            [
                'category', // entity type.
                [], // sort order specifications.
                [   // expected built sort orders.
                    [
                        'type' => SortOrderInterface::TYPE_STANDARD,
                        'field' => SortOrderInterface::DEFAULT_SORT_FIELD,
                        'direction' => SortOrderInterface::SORT_DESC,
                    ],
                    [
                        'type' => SortOrderInterface::TYPE_STANDARD,
                        'field' => 'id',
                        'direction' => SortOrderInterface::SORT_DESC,
                    ],
                ],
            ],
            [
                'category', // entity type.
                [   // sort order specifications.
                    'id' => [
                        'field' => 'id',
                        'direction' => SortOrderInterface::SORT_ASC,
                    ],
                ],
                [   // expected built sort orders.
                    [
                        'type' => SortOrderInterface::TYPE_STANDARD,
                        'field' => 'id',
                        'direction' => SortOrderInterface::SORT_ASC,
                    ],
                    [
                        'type' => SortOrderInterface::TYPE_STANDARD,
                        'field' => SortOrderInterface::DEFAULT_SORT_FIELD,
                        'direction' => SortOrderInterface::SORT_DESC,
                    ],
                ],
            ],
            [
                'category', // entity type.
                [   // sort order specifications.
                    'id' => [
                        'field' => 'id',
                        'direction' => SortOrderInterface::SORT_DESC,
                    ],
                ],
                [   // expected built sort orders.
                    [
                        'type' => SortOrderInterface::TYPE_STANDARD,
                        'field' => 'id',
                        'direction' => SortOrderInterface::SORT_DESC,
                    ],
                    [
                        'type' => SortOrderInterface::TYPE_STANDARD,
                        'field' => SortOrderInterface::DEFAULT_SORT_FIELD,
                        'direction' => SortOrderInterface::SORT_ASC,
                    ],
                ],
            ],
            /* Not possible yet (analyzers not yet introduced in mapping)
            [
                'category',
                [
                    'name' => [
                        'field' => 'name',
                        'direction' => SortOrderInterface::SORT_ASC,
                    ],
                ],
                [
                    [
                        'name' => [
                            'field' => 'name',
                            'direction' => SortOrderInterface::SORT_ASC,
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
            ],
            [
                'category',
                [
                    'name' => [
                        'field' => 'name',
                        'direction' => SortOrderInterface::SORT_DESC,
                    ],
                ],
                [
                    [
                        'name' => [
                            'field' => 'name',
                            'direction' => SortOrderInterface::SORT_DESC,
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
            ],
            */
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
