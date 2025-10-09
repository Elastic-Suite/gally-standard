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

namespace Gally\Test;

use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Gally\Locale\EventSubscriber\LocaleSubscriber;
use Gally\User\Entity\User;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @codeCoverageIgnore
 */
abstract class AbstractEntityTestCase extends AbstractTestCase
{
    private ?ResourceMetadataCollection $resourceMetadataCollection = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::loadFixture(static::getFixtureFiles());
        $fileToMove = static::getFileDirectoriesToCopy();
        if (!empty($fileToMove)) {
            $filesToCopy = static::getFileDirectoriesToCopy();
            foreach ($filesToCopy as $fileToCopy) {
                static::copyDirectoryFiles($fileToCopy['from_path'], $fileToCopy['to_path']);
            }
        }
    }

    abstract protected static function getFixtureFiles(): array;

    abstract protected function getEntityClass(): string;

    /**
     * @return array{from_path: string, to_path: string}[]
     */
    protected static function getFileDirectoriesToCopy(): array
    {
        return [];
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(
        ?User $user,
        array $data,
        int $responseCode = 201,
        ?string $message = null,
        ?string $validRegex = null,
        array $files = [],
    ): void {
        $headers = [];
        $extra = [];
        if (!empty($files)) {
            $headers = ['Content-Type' => 'multipart/form-data'];
            $extra['files'] = $files;
        }

        $request = new RequestToTest('POST', $this->getApiPath(), $user, $data, $headers, $extra);
        $expectedResponse = new ExpectedResponse(
            $responseCode,
            function (ResponseInterface $response) use ($data, $validRegex) {
                $shortName = $this->getShortName();
                $this->assertJsonContains(
                    array_merge(
                        [
                            '@context' => $this->getRoute("contexts/$shortName"),
                            '@type' => $shortName,
                        ],
                        $this->getJsonCreationValidation($data)
                    ),
                    false
                );
                $this->assertMatchesRegularExpression($validRegex ?? '~^.*/?' . $this->getApiPath() . '/\d+$~', $response->toArray()['@id']);
                $this->assertMatchesResourceItemJsonSchema($this->getEntityClass());
            },
            $message
        );

        $this->validateApiCall($request, $expectedResponse);
    }

    /**
     * Data provider for entity creation api call
     * The data provider should return test case with :
     * - User $user: user to use in the api call
     * - array $data: post data
     * - (optional) int $responseCode: expected response code
     * - (optional) string $message: expected error message
     * - (optional) string $validRegex: a regexp used to validate generated id.
     * - (optional) array $files: files to upload.
     */
    abstract public function createDataProvider(): iterable;

    protected function getJsonCreationValidation(array $expectedData): array
    {
        return $expectedData;
    }

    /**
     * @dataProvider getDataProvider
     *
     * @depends testCreate
     */
    public function testGet(?User $user, int|string $id, array $expectedData, int $responseCode, ?string $locale = null): void
    {
        $headers = null !== $locale ? [LocaleSubscriber::GALLY_LANGUAGE_HEADER => $locale] : [];
        $request = new RequestToTest('GET', "{$this->getApiPath()}/{$id}", $user, [], $headers);
        $expectedResponse = new ExpectedResponse(
            $responseCode,
            function (ResponseInterface $response) use ($expectedData) {
                $shortName = $this->getShortName();
                if ($response->getStatusCode() < 400) {
                    $this->assertJsonContains(
                        array_merge(
                            [
                                '@context' => $this->getRoute("contexts/$shortName"),
                                '@type' => $shortName,
                                '@id' => $this->getUri($this->getApiPath(), $expectedData['id']),
                            ],
                            $this->getJsonGetValidation($expectedData)
                        )
                    );
                } else {
                    $this->assertJsonContains(['@context' => $this->getRoute("contexts/$shortName"), '@type' => $shortName]);
                }
            }
        );

        $this->validateApiCall($request, $expectedResponse);
    }

    /**
     * Data provider for entity get api call
     * The data provider should return test case with :
     * - User $user: user to use in the api call
     * - int|string $id: id of the entity to get
     * - array $expectedData: expected data of the entity
     * - int $responseCode: expected response code.
     */
    abstract public function getDataProvider(): iterable;

    protected function getJsonGetValidation(array $expectedData): array
    {
        return $expectedData;
    }

    /**
     * @dataProvider deleteDataProvider
     *
     * @depends testGet
     */
    public function testDelete(?User $user, int|string $id, int $responseCode): void
    {
        $this->validateApiCall(
            new RequestToTest('DELETE', "{$this->getApiPath()}/{$id}", $user),
            new ExpectedResponse(
                $responseCode,
                function (ResponseInterface $response) {
                    $this->assertJsonContains($this->getJsonDeleteValidation());
                }
            )
        );
    }

    /**
     * Data provider for delete entity api call
     * The data provider should return test case with :
     * - User $user: user to use in the api call
     * - int|string $id: id of the entity to delete
     * - int $responseCode: expected response code.
     */
    abstract public function deleteDataProvider(): iterable;

    protected function getJsonDeleteValidation(): array
    {
        return [];
    }

    /**
     * @dataProvider getCollectionDataProvider
     *
     * @depends testDelete
     */
    public function testGetCollection(?User $user, int $expectedItemNumber, int $responseCode): void
    {
        $request = new RequestToTest('GET', $this->getApiPath(), $user);
        $expectedResponse = new ExpectedResponse(
            $responseCode,
            function (ResponseInterface $response) use ($expectedItemNumber) {
                $shortName = $this->getShortName();
                if ($response->getStatusCode() < 400) {
                    $this->assertJsonContains(
                        array_merge(
                            [
                                '@context' => $this->getRoute("contexts/$shortName"),
                                '@id' => $this->getRoute($this->getApiPath()),
                                '@type' => 'hydra:Collection',
                                'hydra:totalItems' => $expectedItemNumber,
                            ],
                            $this->getJsonGetCollectionValidation()
                        )
                    );
                } else {
                    $this->assertJsonContains(['@context' => $this->getRoute("contexts/$shortName"), '@type' => 'hydra:Collection']);
                }
            }
        );

        $this->validateApiCall($request, $expectedResponse);
    }

    /**
     * Data provider for collection api call
     * The data provider should return test case with :
     * - User $user: user to use in the api call
     * - int $expectedItemNumber: the expected number of item in the collection
     * - int $responseCode: expected response code.
     */
    abstract public function getCollectionDataProvider(): iterable;

    protected function getJsonGetCollectionValidation(): array
    {
        return [];
    }

    protected function getShortName(): string
    {
        if (!$this->resourceMetadataCollection) {
            $resourceMetadataCollectionFactory = static::getContainer()->get('api_platform.metadata.resource.metadata_collection_factory');
            $this->resourceMetadataCollection = $resourceMetadataCollectionFactory->create($this->getEntityClass());
        }

        return $this->resourceMetadataCollection[0]->getShortName() ?? '';
    }

    protected function getApiPath(): string
    {
        $pathGenerator = static::getContainer()->get('api_platform.path_segment_name_generator');

        return $pathGenerator->getSegmentName($this->getShortName());
    }
}
