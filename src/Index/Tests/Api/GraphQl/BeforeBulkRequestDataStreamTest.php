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

namespace Gally\Index\Tests\Api\GraphQl;

use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Index\Tests\EventSubscriber\TestBeforeBulkRequestRecorder;
use Gally\Test\AbstractTestCase;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestGraphQlToTest;
use Gally\User\Constant\Role;

class BeforeBulkRequestDataStreamTest extends AbstractTestCase
{
    private const DATA_STREAM_NAME = 'gally_test__gally_localized_catalog_b2c_en_test_event';

    private static int $localizedCatalogId;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::loadFixture([
            __DIR__ . '/../../fixtures/catalogs.yaml',
            __DIR__ . '/../../fixtures/source_field.yaml',
            __DIR__ . '/../../fixtures/metadata.yaml',
        ]);

        self::$localizedCatalogId = static::getContainer()->get(LocalizedCatalogRepository::class)->findByCodeOrId('b2c_en')->getId();
        self::createEntityElasticsearchDataStream('test_event', self::$localizedCatalogId);
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        self::deleteEntityElasticsearchDataStreams('test_event', self::$localizedCatalogId);
    }

    public function testEventIsDispatchedForDataStreamTarget(): void
    {
        TestBeforeBulkRequestRecorder::$targetNames = [];
        $dataStreamName = self::DATA_STREAM_NAME;
        $data = addslashes((string) json_encode([['id' => uniqid('test_event_'), 'event_type' => 'view', '@timestamp' => date('Y-m-d H:i:s')]]));

        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    mutation {
                      bulkDataStream(input: {name: "{$dataStreamName}", data: "{$data}"}) {
                        dataStream { name }
                      }
                    }
                GQL,
                $this->getUser(Role::ROLE_ADMIN)
            ),
            new ExpectedResponse(200)
        );

        $this->assertSame([self::DATA_STREAM_NAME], TestBeforeBulkRequestRecorder::$targetNames);
    }
}
