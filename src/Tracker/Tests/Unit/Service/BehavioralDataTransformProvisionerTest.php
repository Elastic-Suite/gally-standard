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
use Gally\Index\Entity\Transform;
use Gally\Index\Repository\Transform\TransformRepositoryInterface;
use Gally\Index\Service\IndexOperation;
use Gally\Test\AbstractTestCase;
use Gally\Tracker\Service\BehavioralDataTransformProvisioner;
use OpenSearch\Client;

class BehavioralDataTransformProvisionerTest extends AbstractTestCase
{
    private static BehavioralDataTransformProvisioner $provisioner;

    private static IndexOperation $indexOperation;

    private static LocalizedCatalog $localizedCatalog;

    private static string $behavioralDataAlias;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::loadFixture([
            __DIR__ . '/../../fixtures/catalogs.yaml',
            __DIR__ . '/../../fixtures/source_field.yaml',
            __DIR__ . '/../../fixtures/metadata.yaml',
        ]);

        self::$provisioner = static::getContainer()->get(BehavioralDataTransformProvisioner::class);
        self::$indexOperation = static::getContainer()->get(IndexOperation::class);
        self::$localizedCatalog = static::getContainer()->get(LocalizedCatalogRepository::class)->findByCodeOrId('b2c_en');
        self::$behavioralDataAlias = static::getContainer()->get(IndexSettingsInterface::class)
            ->getIndexAliasFromIdentifier('behavioral_data', self::$localizedCatalog);

        self::createEntityElasticsearchDataStream('tracking_event', self::$localizedCatalog->getId());
        self::loadElasticsearchDocumentFixtures([__DIR__ . '/../../fixtures/tracking_event_for_behavioral_data_documents.json']);
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        self::deleteEntityElasticsearchTransforms('behavioral_data', self::$localizedCatalog->getId());
        self::deleteEntityElasticsearchDataStreams('tracking_event', self::$localizedCatalog->getId());
        static::getContainer()->get(IndexOperation::class)->deleteIndicesByAlias(self::$behavioralDataAlias);
        self::deleteEntityElasticsearchTransforms('tracking_session', self::$localizedCatalog->getId());
        self::deleteEntityElasticsearchIndices('tracking_session', self::$localizedCatalog->getId());
    }

    public function testCreateOrUpdateCreatesTheTransformOnTheWriteBackingIndex(): void
    {
        $transformId = self::$provisioner->createOrUpdate(self::$localizedCatalog);

        $this->assertSame(BehavioralDataTransformProvisioner::TRANSFORM_ID_PREFIX . 'b2c_en', $transformId);
        $this->assertSame('behavioral_data', self::$provisioner->getType());
        $indices = self::$indexOperation->findIndicesByAlias(self::$behavioralDataAlias);
        $this->assertCount(1, $indices);

        $transform = $this->findTransform($transformId);
        $this->assertSame($indices[0]->getName(), $transform->getTargetIndex());
        $this->assertStringStartsWith('.ds-', $transform->getSourceIndex());
        $this->assertTrue($transform->isContinuous());
    }

    /**
     * @depends testCreateOrUpdateCreatesTheTransformOnTheWriteBackingIndex
     */
    public function testCreateOrUpdateReusesTheExistingIndexInsteadOfCreatingANewOne(): void
    {
        self::$provisioner->createOrUpdate(self::$localizedCatalog);
        $before = self::$indexOperation->findIndicesByAlias(self::$behavioralDataAlias);

        self::$provisioner->createOrUpdate(self::$localizedCatalog);

        $after = self::$indexOperation->findIndicesByAlias(self::$behavioralDataAlias);
        $this->assertCount(1, $after);
        $this->assertSame($before[0]->getName(), $after[0]->getName());
    }

    /**
     * @depends testCreateOrUpdateCreatesTheTransformOnTheWriteBackingIndex
     */
    public function testTransformAggregatesProductViewsAndOrdersPerEntityAndDay(): void
    {
        $transport = static::getContainer()->get(Client::class)->transport;

        $transform = $this->findTransform(BehavioralDataTransformProvisioner::TRANSFORM_ID_PREFIX . 'b2c_en');
        $this->assertNotNull($transform->getDataSelectionQuery());
        $response = $transport->resultOrFuture(
            $transport->performRequest('POST', '/_plugins/_transform/_preview', [], ['transform' => $transform->toArray()]),
            []
        );

        $aggregatedValues = [];
        foreach ($response['documents'] as $document) {
            $aggregatedValues[\sprintf('%s|%s', $document['entity_code'], gmdate('Y-m-d', intdiv((int) $document['date'], 1000)))] = [
                $document['views'],
                $document['sales'],
                $document['revenue'],
            ];
        }
        ksort($aggregatedValues);

        // Category events and add_to_cart events are excluded by the data selection query.
        $this->assertSame(
            [
                'P1|2025-01-01' => [2, 2, 30.0],
                'P2|2025-01-02' => [1, 0, 0.0],
            ],
            $aggregatedValues
        );
    }

    /**
     * @depends testTransformAggregatesProductViewsAndOrdersPerEntityAndDay
     * @depends testCreateOrUpdateReusesTheExistingIndexInsteadOfCreatingANewOne
     */
    public function testRemoveDeletesTheTransform(): void
    {
        self::$provisioner->remove(self::$localizedCatalog);

        $this->assertFalse(self::$provisioner->isHealthy(self::$localizedCatalog));
        $this->assertNull($this->findTransform(BehavioralDataTransformProvisioner::TRANSFORM_ID_PREFIX . 'b2c_en'));
    }

    private function findTransform(string $transformId): ?Transform
    {
        foreach (static::getContainer()->get(TransformRepositoryInterface::class)->findAll() as $transform) {
            if ($transform->getId() === $transformId) {
                return $transform;
            }
        }

        return null;
    }
}
