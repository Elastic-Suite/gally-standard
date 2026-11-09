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

use Gally\Fixture\Service\ElasticsearchFixturesInterface;
use Gally\Index\Tests\EventSubscriber\TestDocumentModifier;
use Gally\Test\AbstractTestCase;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestGraphQlToTest;
use Gally\User\Constant\Role;
use Symfony\Contracts\HttpClient\ResponseInterface;

class BeforeBulkRequestEventTest extends AbstractTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::loadElasticsearchIndexFixtures([__DIR__ . '/../../fixtures/indices.json']);
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        self::deleteElasticsearchFixtures();
    }

    public function testDocumentsModifiedByListenerAreIndexed(): void
    {
        $indexName = ElasticsearchFixturesInterface::PREFIX_TEST_INDEX . 'index_product';
        $data = addslashes((string) json_encode([
            'id1' => ['id' => 'id1', 'name' => TestDocumentModifier::DOCUMENT_NAME_TO_MODIFY],
        ]));
        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    mutation {
                      bulkIndex(input: {indexName: "{$indexName}", data: "{$data}"}) {
                        index { name }
                      }
                    }
                GQL,
                $this->getUser(Role::ROLE_ADMIN)
            ),
            new ExpectedResponse(200)
        );

        $document = static::getContainer()->get('opensearch.client.test')->get(['index' => $indexName, 'id' => 'id1']); // @phpstan-ignore-line
        $this->assertSame(
            TestDocumentModifier::DOCUMENT_NAME_TO_MODIFY . TestDocumentModifier::MODIFIED_SUFFIX,
            $document['_source']['name']
        );
    }

    public function testBulkEmptiedByListenerIsRejected(): void
    {
        $indexName = ElasticsearchFixturesInterface::PREFIX_TEST_INDEX . 'index_product';
        $data = addslashes((string) json_encode([
            'id2' => ['id' => 'id2', 'name' => TestDocumentModifier::DOCUMENT_NAME_TO_REMOVE],
        ]));
        $this->validateApiCall(
            new RequestGraphQlToTest(
                <<<GQL
                    mutation {
                      bulkIndex(input: {indexName: "{$indexName}", data: "{$data}"}) {
                        index { name }
                      }
                    }
                GQL,
                $this->getUser(Role::ROLE_ADMIN)
            ),
            new ExpectedResponse(
                200,
                fn (ResponseInterface $response) => $this->assertJsonContains([
                    'errors' => [['message' => 'Internal server error']],
                ])
            )
        );
    }
}
