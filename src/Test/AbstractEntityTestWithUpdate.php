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

namespace Gally\Test;

use Gally\User\Model\User;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @codeCoverageIgnore
 */
abstract class AbstractEntityTestWithUpdate extends AbstractEntityTestCase
{
    /**
     * @dataProvider patchUpdateDataProvider
     *
     * @depends testGet
     */
    public function testPatchUpdate(
        ?User $user,
        int|string $id,
        array $data,
        int $responseCode,
        ?string $message = null,
        ?string $validRegex = null
    ): ResponseInterface {
        return $this->update('PATCH', $user, $id, $data, $responseCode, ['Content-Type' => 'application/merge-patch+json'], $message, $validRegex);
    }

    /**
     * @dataProvider putUpdateDataProvider
     *
     * @depends testPatchUpdate
     */
    public function testPutUpdate(
        ?User $user,
        int|string $id,
        array $data,
        int $responseCode,
        ?string $message = null,
        ?string $validRegex = null
    ): ResponseInterface {
        return $this->update('PUT', $user, $id, $data, $responseCode, ['Content-Type' => 'application/ld+json'], $message, $validRegex);
    }

    /**
     * Data provider for entity update api call
     * The data provider should return test case with :
     * - User $user: user to use in the api call
     * - int|string $id: id of the entity to update
     * - array $data: post data
     * - (optional) int $responseCode: expected response code
     * - (optional) string $message: expected error message
     * - (optional) string $validRegex: a regexp used to validate generated id.
     */
    abstract public function patchUpdateDataProvider(): iterable;

    /**
     * Data provider for entity update api call
     * The data provider should return test case with :
     * - User $user: user to use in the api call
     * - int|string $id: id of the entity to update
     * - array $data: post data
     * - (optional) int $responseCode: expected response code
     * - (optional) string $message: expected error message
     * - (optional) string $validRegex: a regexp used to validate generated id.
     */
    public function putUpdateDataProvider(): iterable
    {
        return $this->patchUpdateDataProvider();
    }

    protected function update(
        string $method,
        ?User $user,
        int|string $id,
        array $data,
        int $responseCode,
        array $headers = [],
        ?string $message = null,
        ?string $validRegex = null
    ): ResponseInterface {
        $request = new RequestToTest($method, "{$this->getApiPath()}/{$id}", $user, $data, $headers);
        $expectedResponse = new ExpectedResponse(
            $responseCode,
            function (ResponseInterface $response) use ($data, $validRegex) {
                $shortName = $this->getShortName();
                $this->assertJsonContains(
                    array_merge(
                        ['@context' => "/contexts/$shortName", '@type' => $shortName],
                        $this->getJsonUpdateValidation($data)
                    )
                );
                $this->assertMatchesRegularExpression($validRegex ?? '~^' . $this->getApiPath() . '/\d+$~', $response->toArray()['@id']);
                $this->assertMatchesResourceItemJsonSchema($this->getEntityClass());
            },
            $message
        );

        return $this->validateApiCall($request, $expectedResponse);
    }

    protected function getJsonUpdateValidation(array $expectedData): array
    {
        /*
         * On PUT or PATCH requests, when we want to update a sub-resource we send its id via the key '@id'.
         * In the response, If a "replace" was made on the sub-resource instead of an "update", we can have an id  different from the one sent in request.
         * By default, the $expectedData are equal to the data sent from the request.
         * Therefore, we need to remove ids from $expectedData,
         * because we know that the ids in the request and in the response will be different if a "replace" is applied on the sub-resource.
         */
        $this->removeId($expectedData);

        return $expectedData;
    }

    /**
     * Remove in $node array all the elements with the key '@id'.
     */
    protected function removeId(array &$node): void
    {
        foreach ($node as $key => &$item) {
            if (\is_array($item)) {
                $this->removeId($item);
            }
            if ('@id' === $key) {
                unset($node['@id']);
            }
        }
    }

    /**
     * @dataProvider deleteDataProvider
     *
     * @depends testPutUpdate
     */
    public function testDelete(?User $user, int|string $id, int $responseCode): void
    {
        parent::testDelete($user, $id, $responseCode);
    }
}
