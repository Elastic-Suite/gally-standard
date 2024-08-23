<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @package   Gally
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Gally\Index\Tests\Api\Rest;

use Gally\Test\AbstractTestCase;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestToTest;
use Gally\User\Constant\Role;
use Gally\User\Model\User;

class IndexDocumentTest extends AbstractTestCase
{
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

    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(
        ?User $user,
        array $data,
        int $responseCode = 201,
        ?string $message = null,
    ): void {
        $request = new RequestToTest('POST', '/index_documents', $user, $data);
        $expectedResponse = new ExpectedResponse($responseCode, null, $message);

        $this->validateApiCall($request, $expectedResponse);
    }

    /**
     * Data provider for entity creation api call
     * The data provider should return test case with :
     * - User $user: user to use in the api call
     * - array $data: post data
     * - (optional) int $responseCode: expected response code.
     */
    public function createDataProvider(): iterable
    {
        $data = [
            'indexName' => 'gally_test__gally_b2c_fr_product',
            'documents' => [json_encode(['entity_id' => 1, 'name' => 'Product 1'])],
        ];

        return [
            [null, $data, 401, 'Access Denied.'],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), $data, 403, 'Access Denied.'],
            [$this->getUser(Role::ROLE_ADMIN), $data, 201],
        ];
    }

    /**
     * @dataProvider deleteDataProvider
     */
    public function testDelete(
        ?User $user,
        string $indexName,
        array $ids,
        int $responseCode = 204,
        ?string $message = null,
    ): void {
        $this->validateApiCall(
            new RequestToTest('DELETE', "/index_documents/$indexName", $user, ['document_ids' => $ids]),
            new ExpectedResponse($responseCode, null, $message)
        );
    }

    /**
     * Data provider for entity creation api call
     * The data provider should return test case with :
     * - User $user: user to use in the api call
     * - string $indexName : the name of the index to delete data from
     * - array $documentsIds : Document ids to remove
     * - (optional) int $responseCode: expected response code.
     */
    public function deleteDataProvider(): iterable
    {
        return [
            [null, 'gally_test__gally_b2c_fr_product', [], 401, 'Access Denied.'],
            [$this->getUser(Role::ROLE_CONTRIBUTOR), 'gally_test__gally_b2c_fr_product', [], 403, 'Access Denied.'],
            [$this->getUser(Role::ROLE_ADMIN), 'wrong_index_name', ['1'], 400, 'The index wrong_index_name does not exist.'],
            [$this->getUser(Role::ROLE_ADMIN), 'gally_test__gally_b2c_fr_product', ['1']],
        ];
    }
}
