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

namespace Gally\Index\Tests\Api\Rest;

use Gally\Index\Tests\EventSubscriber\TestDocumentModifier;
use Gally\Test\AbstractTestCase;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestToTest;
use Gally\User\Constant\Role;

class BeforeBulkRequestEventTest extends AbstractTestCase
{
    private const INDEX_NAME = 'gally_test__gally_localized_catalog_b2c_fr_product';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::loadFixture([
            __DIR__ . '/../../fixtures/catalogs.yaml',
            __DIR__ . '/../../fixtures/source_field.yaml',
            __DIR__ . '/../../fixtures/metadata.yaml',
        ]);

        self::createEntityElasticsearchIndices('product');
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        self::deleteElasticsearchFixtures();
    }

    public function testDocumentsModifiedByListenerAreIndexed(): void
    {
        $data = [
            'indexName' => self::INDEX_NAME,
            'documents' => [json_encode(['entity_id' => 1, 'name' => TestDocumentModifier::DOCUMENT_NAME_TO_MODIFY])],
        ];
        $this->validateApiCall(
            new RequestToTest('POST', 'index_documents', $this->getUser(Role::ROLE_ADMIN), $data),
            new ExpectedResponse(201)
        );

        $document = static::getContainer()->get('opensearch.client.test')->get(['index' => self::INDEX_NAME, 'id' => '1']); // @phpstan-ignore-line
        $this->assertSame(
            TestDocumentModifier::DOCUMENT_NAME_TO_MODIFY . TestDocumentModifier::MODIFIED_SUFFIX,
            $document['_source']['name']
        );
    }

    public function testBulkEmptiedByListenerIsRejected(): void
    {
        $data = [
            'indexName' => self::INDEX_NAME,
            'documents' => [json_encode(['entity_id' => 2, 'name' => TestDocumentModifier::DOCUMENT_NAME_TO_REMOVE])],
        ];
        $this->validateApiCall(
            new RequestToTest('POST', 'index_documents', $this->getUser(Role::ROLE_ADMIN), $data),
            new ExpectedResponse(400)
        );
    }
}
