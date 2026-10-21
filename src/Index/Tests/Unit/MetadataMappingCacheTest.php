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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Gally\Cache\Service\CacheManagerInterface;
use Gally\Index\Entity\Index\Mapping;
use Gally\Index\Service\MetadataManager;
use Gally\Metadata\Entity\Metadata;
use Gally\Metadata\Repository\MetadataRepository;
use Gally\Metadata\Repository\SourceFieldRepository;
use Gally\Test\AbstractTestCase;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestToTest;
use Gally\User\Constant\Role;

/**
 * Check that the metadata mapping cache is invalidated when source fields are updated.
 */
class MetadataMappingCacheTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        self::loadFixture([
            __DIR__ . '/../fixtures/catalogs.yaml',
            __DIR__ . '/../fixtures/source_field.yaml',
            __DIR__ . '/../fixtures/metadata.yaml',
        ]);
    }

    /**
     * Source fields bulk data are written without the ORM, the mapping cache must be invalidated anyway.
     */
    public function testMappingCacheInvalidatedOnBulk(): void
    {
        $metadata = $this->getProductMetadata();
        $this->assertNull($this->getMappingProperty('name'));

        $this->validateApiCall(
            new RequestToTest(
                'POST',
                'source_fields/bulk',
                $this->getUser(Role::ROLE_ADMIN),
                [['code' => 'name', 'metadata' => $this->getUri('metadata', $metadata->getId()), 'isFilterable' => true]]
            ),
            new ExpectedResponse(200)
        );

        $this->assertSame('name.untouched', $this->getMappingProperty('name'));
    }

    /**
     * Simulate a concurrent request rebuilding the mapping cache from the not yet committed data:
     * the mapping cache must be invalidated again after the commit.
     */
    public function testMappingCacheInvalidatedAfterCommit(): void
    {
        $metadata = $this->getProductMetadata();
        $this->assertNull($this->getMappingProperty('name'));
        $staleMapping = $this->getMetadataManager()->getMapping($metadata);

        $cacheManager = static::getContainer()->get(CacheManagerInterface::class);
        $concurrentRequest = new class($cacheManager, $staleMapping, $metadata->getEntity()) {
            public function __construct(
                private CacheManagerInterface $cacheManager,
                private Mapping $staleMapping,
                private string $entity,
            ) {
            }

            public function postUpdate(): void
            {
                // Same cache entry as the one built in MetadataManager::getMapping.
                // The entry may already have been rebuilt by another listener of the current flush, using the same
                // connection as the update and so the up-to-date data: delete it before writing the stale mapping.
                $cacheKey = 'gally_metadata_mapping_' . md5($this->entity);
                $this->cacheManager->delete($cacheKey);
                $this->cacheManager->get(
                    $cacheKey,
                    fn () => $this->staleMapping,
                    [MetadataManager::CACHE_TAG_METADATA_MAPPING],
                );
            }
        };

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        // Added after the gally listeners, so it is called once the cache has been invalidated in postUpdate.
        $entityManager->getEventManager()->addEventListener(Events::postUpdate, $concurrentRequest);

        try {
            $sourceField = static::getContainer()->get(SourceFieldRepository::class)
                ->findOneBy(['metadata' => $metadata, 'code' => 'name']);
            $sourceField->setIsFilterable(true);
            $entityManager->flush();
        } finally {
            $entityManager->getEventManager()->removeEventListener(Events::postUpdate, $concurrentRequest);
        }

        // Reboot the kernel to get rid of the in-memory caches and read the mapping from redis, like the next request would.
        self::ensureKernelShutdown();
        self::bootKernel();

        $this->assertSame('name.untouched', $this->getMappingProperty('name'));
    }

    private function getProductMetadata(): Metadata
    {
        return static::getContainer()->get(MetadataRepository::class)->findByEntity('product', false);
    }

    private function getMetadataManager(): MetadataManager
    {
        return static::getContainer()->get(MetadataManager::class);
    }

    private function getMappingProperty(string $fieldName): ?string
    {
        // Reload services and metadata as the kernel may have been rebooted by the api call.
        return $this->getMetadataManager()->getMapping($this->getProductMetadata())->getField($fieldName)->getMappingProperty();
    }
}
