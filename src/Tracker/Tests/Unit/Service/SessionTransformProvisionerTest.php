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

namespace Gally\Tracker\Tests\Unit\Service;

use Gally\Catalog\Entity\LocalizedCatalog;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Index\Api\IndexSettingsInterface;
use Gally\Index\Repository\Transform\TransformRepositoryInterface;
use Gally\Index\Service\IndexOperation;
use Gally\Test\AbstractTestCase;
use Gally\Tracker\Service\SessionTransformProvisioner;
use OpenSearch\Client;

class SessionTransformProvisionerTest extends AbstractTestCase
{
    private static SessionTransformProvisioner $provisioner;

    private static IndexOperation $indexOperation;

    private static LocalizedCatalog $localizedCatalog;

    private static string $sessionAlias;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::loadFixture([
            __DIR__ . '/../../fixtures/catalogs.yaml',
            __DIR__ . '/../../fixtures/source_field.yaml',
            __DIR__ . '/../../fixtures/metadata.yaml',
        ]);

        self::$provisioner = static::getContainer()->get(SessionTransformProvisioner::class);
        self::$indexOperation = static::getContainer()->get(IndexOperation::class);
        self::$localizedCatalog = static::getContainer()->get(LocalizedCatalogRepository::class)->findByCodeOrId('b2c_en');
        self::$sessionAlias = static::getContainer()->get(IndexSettingsInterface::class)
            ->getIndexAliasFromIdentifier('tracking_session', self::$localizedCatalog);

        self::createEntityElasticsearchDataStream('tracking_event', self::$localizedCatalog->getId());
        self::loadElasticsearchDocumentFixtures([
            __DIR__ . '/../../fixtures/tracking_event_for_session_documents.json',
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        self::deleteEntityElasticsearchTransforms('tracking_session', self::$localizedCatalog->getId());
        self::deleteEntityElasticsearchDataStreams('tracking_event', self::$localizedCatalog->getId());
        self::deleteEntityElasticsearchIndices('tracking_session', self::$localizedCatalog->getId());
    }

    /**
     * The basic "creates a healthy transform over a real target index" case is covered by
     * TrackingEventHandlerTest::testHandleTrackingEventProvisionsARealTrackingSession, which
     * exercises it through the real production entry point instead of calling this service
     * directly.
     */
    public function testCreateOrUpdateReusesTheExistingIndexInsteadOfCreatingANewOne(): void
    {
        self::$provisioner->createOrUpdate(self::$localizedCatalog);
        $before = self::$indexOperation->findIndicesByAlias(self::$sessionAlias);
        $this->assertCount(1, $before);

        self::$provisioner->createOrUpdate(self::$localizedCatalog);

        $after = self::$indexOperation->findIndicesByAlias(self::$sessionAlias);
        $this->assertCount(1, $after);
        $this->assertSame($before[0]->getName(), $after[0]->getName());
    }

    public function testSearchesResultsCountIsTheAverageOfSearchesWithoutFilters(): void
    {
        self::$provisioner->createOrUpdate(self::$localizedCatalog);
        $transform = static::getContainer()->get(TransformRepositoryInterface::class)
            ->findAll()[0];

        $response = static::getContainer()->get(Client::class)->search([
            'index' => $transform->getSourceIndex(),
            'body' => [
                'size' => 0,
                'query' => ['term' => ['session_uid' => '33333333-3333-3333-3333-333333333333']],
                'aggs' => ['searches' => $transform->getAggregations()['searches']],
            ],
        ]);

        $resultsCountByQuery = array_column($response['aggregations']['searches']['value'], 'results_count', 'query');
        $this->assertEquals(
            [
                'boots' => 18, // Paginated searches share the same count, the filtered search is ignored.
                'sandals' => 4, // Only filtered searches: average of all searches.
                'hats' => 5, // Missing item_count counts as 0: average of 10 and 0.
                'shoes' => 11, // Average of 10 and 11 is rounded.
            ],
            $resultsCountByQuery
        );
    }

    public function testSearchesAreSpellcheckedOnlyWhenAllTheSearchesOfTheTermAreSpellchecked(): void
    {
        self::$provisioner->createOrUpdate(self::$localizedCatalog);
        $transform = static::getContainer()->get(TransformRepositoryInterface::class)
            ->findAll()[0];

        $response = static::getContainer()->get(Client::class)->search([
            'index' => $transform->getSourceIndex(),
            'body' => [
                'size' => 0,
                'query' => ['term' => ['session_uid' => '55555555-5555-5555-5555-555555555555']],
                'aggs' => ['searches' => $transform->getAggregations()['searches']],
            ],
        ]);

        $isSpellcheckedByQuery = array_column($response['aggregations']['searches']['value'], 'is_spellchecked', 'query');
        $this->assertEquals(
            [
                'all' => true,
                'mixed' => false,
                'none' => false,
                'missing' => false, // A missing value counts as not spellchecked.
                'missing and spellchecked' => false,
            ],
            $isSpellcheckedByQuery
        );
    }

    public function testProductEventFieldsContainDistinctProductSkusOnly(): void
    {
        self::$provisioner->createOrUpdate(self::$localizedCatalog);
        $transform = static::getContainer()->get(TransformRepositoryInterface::class)
            ->findAll()[0];
        $aggregations = $transform->getAggregations();

        $response = static::getContainer()->get(Client::class)->search([
            'index' => $transform->getSourceIndex(),
            'body' => [
                'size' => 0,
                'query' => ['term' => ['session_uid' => '77777777-7777-7777-7777-777777777777']],
                'aggs' => [
                    'product_view' => $aggregations['product_view'],
                    'product_cart' => $aggregations['product_cart'],
                    'product_order' => $aggregations['product_order'],
                ],
            ],
        ]);

        // Repeated views are deduplicated and the category view is ignored.
        $this->assertEqualsCanonicalizing(['SKU1', 'SKU2'], $response['aggregations']['product_view']['value']);
        $this->assertEqualsCanonicalizing(['SKU2'], $response['aggregations']['product_cart']['value']);
        $this->assertEqualsCanonicalizing(['SKU2', 'SKU3'], $response['aggregations']['product_order']['value']);
    }

    /**
     * Relies on the transform writing aggregation values after its own _doc_count: the preview API
     * builds documents the same way, so this fails if an OpenSearch upgrade changes that behavior.
     */
    public function testSessionDocumentsWeighOneInBucketAggregations(): void
    {
        self::$provisioner->createOrUpdate(self::$localizedCatalog);
        $transform = static::getContainer()->get(TransformRepositoryInterface::class)
            ->findAll()[0];

        $client = static::getContainer()->get(Client::class);
        $response = $client->transport->resultOrFuture(
            $client->transport->performRequest('POST', '/_plugins/_transform/_preview', [], ['transform' => $transform->toArray()]),
            []
        );

        $documentsBySession = array_column($response['documents'], null, 'session_uid');
        $this->assertNotEmpty($documentsBySession);
        foreach ($documentsBySession as $document) {
            $this->assertSame(1, $document['_doc_count']);
        }
        $this->assertSame(7, $documentsBySession['77777777-7777-7777-7777-777777777777']['transform._doc_count']);
    }

    /**
     * @depends testCreateOrUpdateReusesTheExistingIndexInsteadOfCreatingANewOne
     */
    public function testRemoveStopsAndDeletesTheTransform(): void
    {
        self::$provisioner->remove(self::$localizedCatalog);

        $this->assertFalse(self::$provisioner->isHealthy(self::$localizedCatalog));
    }
}
