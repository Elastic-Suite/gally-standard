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

namespace Gally\Search\Tests\Unit\Elasticsearch\Adapter\Common\Response;

use Gally\Search\Elasticsearch\Adapter\Common\Response;
use Gally\Search\Elasticsearch\Adapter\Common\Response\Aggregation;
use Gally\Search\Elasticsearch\Adapter\Common\Response\BucketValue;
use Gally\Search\Elasticsearch\Builder\Response\AggregationBuilder;
use Gally\Search\Elasticsearch\DocumentInterface;
use Gally\Search\Entity\Document;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class QueryResponseTest extends KernelTestCase
{
    /**
     * @dataProvider queryResponseDocumentsOnlyDataProvider
     *
     * @param array $searchResponse    Raw search response from Elasticsearch
     * @param int   $expectedDocsCount Expected documents count in the query response
     */
    public function testTraversableCountable(array $searchResponse, int $expectedDocsCount): void
    {
        $queryResponse = new Response\QueryResponse([], $searchResponse, new AggregationBuilder());
        $this->assertCount($expectedDocsCount, $queryResponse);
        $this->assertContainsOnlyInstancesOf(DocumentInterface::class, $queryResponse);
    }

    public function queryResponseDocumentsOnlyDataProvider(): array
    {
        return [
            [
                [
                    'hits' => [
                        'hits' => [
                            [
                                '_id' => '1',
                                '_score' => 1.0,
                                '_source' => [
                                    ['field' => 'value'],
                                ],
                            ],
                        ],
                        'total' => 1,
                    ],
                ],
                1,
            ],
            [
                [
                    'hits' => [
                        'hits' => [
                            [
                                '_id' => '1',
                                '_score' => 1.1,
                                '_source' => [
                                    ['field' => 'value'],
                                ],
                            ],
                            [
                                '_id' => '2',
                                '_score' => 1.0,
                                '_source' => [
                                    ['field1' => 'value1', 'field2' => 'value2'],
                                ],
                            ],
                        ],
                        'total' => ['value' => 2],
                    ],
                ],
                2,
            ],
            [
                [
                    'hits' => [
                        'hits' => [],
                        'total' => 0,
                    ],
                ],
                0,
            ],
            [
                [],
                0,
            ],
        ];
    }

    /**
     * @dataProvider queryResponseDocumentsDataAndAggregationsDataProvider
     *
     * @param array                           $searchRequest        Raw search request from Elasticsearch
     * @param array                           $searchResponse       Raw search response from Elasticsearch
     * @param int                             $expectedDocsCount    Expected documents count in the query response
     * @param Document[]                      $expectedDocuments    Expected documents in the query response
     * @param Response\AggregationInterface[] $expectedAggregations Expected aggregations in the query response
     * @param int                             $expectedTotalItems   Expected total items count matching the query
     *
     * @throws \Exception
     */
    public function testDocumentsAndAggregationsExtraction(
        array $searchRequest,
        array $searchResponse,
        int $expectedDocsCount,
        array $expectedDocuments,
        array $expectedAggregations,
        int $expectedTotalItems
    ): void {
        $response = new Response\QueryResponse($searchRequest, $searchResponse, new AggregationBuilder());
        $this->assertContainsOnlyInstancesOf(DocumentInterface::class, $response);
        $this->assertCount($expectedDocsCount, $response);
        $this->assertEquals($expectedDocsCount, $response->count());
        $this->assertEquals($expectedDocuments, iterator_to_array($response->getIterator()));
        $this->assertEquals($expectedAggregations, $response->getAggregations());
        $this->assertEquals($expectedTotalItems, $response->getTotalItems());
    }

    public function queryResponseDocumentsDataAndAggregationsDataProvider(): array
    {
        $docTest1 = new Document(
            [
                '_id' => '1',
                '_score' => 1.0,
                '_source' => [['field' => 'value']],
            ]
        );
        $docTest2 = new Document(
            [
                '_id' => '2',
                '_score' => 1.1,
                '_source' => [['field1' => 'value1', 'field2' => 'value2']],
            ]
        );
        $aggregation = new Aggregation(
            'brand',
            ['Brand1' => new BucketValue('Brand1', 3, []), '__other_docs' => new BucketValue('__other_docs', 2, [])],
            5
        );

        return [
            [
                [],
                [
                    'hits' => [
                        'hits' => [
                            [
                                '_id' => '1',
                                '_score' => 1.0,
                                '_source' => [
                                    ['field' => 'value'],
                                ],
                            ],
                        ],
                        'total' => ['value' => 1],
                    ],
                ],
                1,
                [$docTest1],
                [],
                1,
            ],
            [
                [],
                [
                    'hits' => [
                        'hits' => [
                            [
                                '_id' => '1',
                                '_score' => 1.0,
                                '_source' => [
                                    ['field' => 'value'],
                                ],
                            ],
                            [
                                '_id' => '2',
                                '_score' => 1.1,
                                '_source' => [
                                    ['field1' => 'value1', 'field2' => 'value2'],
                                ],
                            ],
                        ],
                        'total' => ['value' => 2],
                    ],
                ],
                2,
                [$docTest1, $docTest2],
                [],
                2,
            ],
            [
                [
                    'body' => [
                        'aggregations' => [
                            'brand' => ['term'],
                        ],
                    ],
                ],
                [
                    'hits' => [
                        'hits' => [
                            [
                                '_id' => '1',
                                '_score' => 1.0,
                                '_source' => [
                                    ['field' => 'value'],
                                ],
                            ],
                            [
                                '_id' => '2',
                                '_score' => 1.1,
                                '_source' => [
                                    ['field1' => 'value1', 'field2' => 'value2'],
                                ],
                            ],
                        ],
                        'total' => ['value' => 2],
                    ],
                    'aggregations' => [
                        'brand' => [
                            'doc_count_error_upper_bound' => 0,
                            'sum_other_doc_count' => 2,
                            'buckets' => [
                                ['key' => 'Brand1', 'doc_count' => 3],
                            ],
                        ],
                    ],
                ],
                2,
                [$docTest1, $docTest2],
                ['brand' => $aggregation],
                2,
            ],
            [
                [],
                [
                    'hits' => [
                        'hits' => [],
                        'total' => 0,
                    ],
                ],
                0,
                [],
                [],
                0,
            ],
            [
                [],
                [],
                0,
                [],
                [],
                0,
            ],
        ];
    }
}
