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

namespace Gally\Search\Tests\Api\Rest;

use Gally\Search\Tests\Api\GraphQl\DocumentSourceFieldLabelTest as GraphQlDocumentSourceFieldLabelTest;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestToTest;
use Symfony\Contracts\HttpClient\ResponseInterface;

class DocumentSourceFieldLabelTest extends GraphQlDocumentSourceFieldLabelTest
{
    /**
     * @dataProvider getLabelsDataProvider
     *
     * @param string[]                                       $codes
     * @param array<int, array{code: string, label: string}> $expectedData
     */
    public function testGetCollection(string $entityType, array $codes, string $localizedCatalog, array $expectedData): void
    {
        // No user is passed, so no bearer token is sent: the endpoint must answer anonymously.
        $this->validateApiCall(
            new RequestToTest('GET', $this->buildPath($entityType, $codes, $localizedCatalog), null),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use ($expectedData) {
                    $this->assertJsonContains(['hydra:member' => $expectedData]);
                }
            )
        );
    }

    public function testMissingCodes(): void
    {
        $this->validateApiCall(
            new RequestToTest('GET', 'document_source_field_labels?entityType=product_document&localizedCatalog=b2b_fr', null),
            new ExpectedResponse(400, null, 'The codes argument is required and cannot be empty.')
        );
    }

    public function testEmptyEntityType(): void
    {
        $this->validateApiCall(
            new RequestToTest('GET', 'document_source_field_labels?codes[]=size&localizedCatalog=b2b_fr', null),
            new ExpectedResponse(400, null, 'The entityType argument is required.')
        );
    }

    public function testUnknownEntityType(): void
    {
        $this->validateApiCall(
            new RequestToTest('GET', $this->buildPath('not_an_entity', ['size'], 'b2b_fr'), null),
            new ExpectedResponse(400, null, 'Entity type [not_an_entity] does not exist')
        );
    }

    public function testMissingLocalizedCatalog(): void
    {
        $this->validateApiCall(
            new RequestToTest('GET', 'document_source_field_labels?entityType=product_document&codes[]=size', null),
            new ExpectedResponse(400, null, 'The localizedCatalog argument is required.')
        );
    }

    public function testUnknownLocalizedCatalog(): void
    {
        $this->validateApiCall(
            new RequestToTest('GET', $this->buildPath('product_document', ['size'], 'not_a_catalog'), null),
            new ExpectedResponse(400, null, 'Missing localized catalog [not_a_catalog]')
        );
    }

    /**
     * There is no item operation, so a single code cannot be probed through a dedicated route.
     */
    public function testNoItemOperation(): void
    {
        $response = $this->request(new RequestToTest('GET', 'document_source_field_labels/size', null));

        $this->assertContains($response->getStatusCode(), [404, 405]);
    }

    /**
     * @param string[] $codes
     */
    private function buildPath(string $entityType, array $codes, string $localizedCatalog): string
    {
        $query = http_build_query(['entityType' => $entityType, 'codes' => $codes, 'localizedCatalog' => $localizedCatalog]);

        return 'document_source_field_labels?' . $query;
    }
}
