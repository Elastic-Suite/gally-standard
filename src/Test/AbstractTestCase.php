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

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Doctrine\Migrations\FilesystemMigrationsRepository;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorage;
use Doctrine\Migrations\MigratorConfiguration;
use Doctrine\Migrations\Version\SortedMigrationPlanCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Gally\Fixture\Service\ElasticsearchFixtures;
use Gally\Fixture\Service\EntityIndicesFixturesInterface;
use Gally\User\Tests\LoginTrait;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @codeCoverageIgnore
 */
abstract class AbstractTestCase extends ApiTestCase
{
    use LoginTrait;

    protected static function loadFixture(array $paths): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
        $dependencyFactory = static::getContainer()->get('doctrine.migrations.dependency_factory');

        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->dropDatabase();

        // Create database schema with migrations
        $migratorConfiguration = (new MigratorConfiguration())
            ->setDryRun(false)
            ->setTimeAllQueries(false)
            ->setAllOrNothing(true);
        $migrationsRepository = new FilesystemMigrationsRepository(
            $dependencyFactory->getConfiguration()->getMigrationClasses(),
            $dependencyFactory->getConfiguration()->getMigrationDirectories(),
            $dependencyFactory->getMigrationsFinder(),
            $dependencyFactory->getMigrationFactory(),
        );
        $planCalculator = new SortedMigrationPlanCalculator(
            $migrationsRepository,
            $dependencyFactory->getMetadataStorage(),
            $dependencyFactory->getVersionComparator(),
        );

        $version = $dependencyFactory->getVersionAliasResolver()->resolveVersionAlias('latest');
        $plan = $planCalculator->getPlanUntilVersion($version);

        // Create a new MetadataStorage object to avoid error with already set properties.
        $metadataStorage = new TableMetadataStorage(
            $dependencyFactory->getConnection(),
            $dependencyFactory->getVersionComparator(),
            $dependencyFactory->getConfiguration()->getMetadataStorageConfiguration(),
            $dependencyFactory->getMigrationRepository(),
        );
        $metadataStorage->ensureInitialized();
        $dependencyFactory->getMigrator()->migrate($plan, $migratorConfiguration);

        // Load alice fixtures with append
        $databaseTool->loadAliceFixture(array_merge(static::getUserFixtures(), $paths), true);
        $entityManager->clear();
    }

    protected static function createEntityElasticsearchIndices(string $entityType, string|int|null $localizedCatalogIdentifier = null)
    {
        $entityIndicesFixtures = static::getContainer()->get(EntityIndicesFixturesInterface::class);
        $entityIndicesFixtures->createEntityElasticsearchIndices($entityType, $localizedCatalogIdentifier);
    }

    protected static function deleteEntityElasticsearchIndices(string $entityType, string|int|null $localizedCatalogIdentifier = null)
    {
        $entityIndicesFixtures = static::getContainer()->get(EntityIndicesFixturesInterface::class);
        $entityIndicesFixtures->deleteEntityElasticsearchIndices($entityType, $localizedCatalogIdentifier);
    }

    protected static function loadElasticsearchIndexFixtures(array $paths)
    {
        $elasticFixtures = static::getContainer()->get(ElasticsearchFixtures::class);
        $elasticFixtures->loadFixturesIndexFiles($paths);
    }

    protected static function loadElasticsearchDocumentFixtures(array $paths)
    {
        $elasticFixtures = static::getContainer()->get(ElasticsearchFixtures::class);
        $elasticFixtures->loadFixturesDocumentFiles($paths);
    }

    protected static function deleteElasticsearchFixtures()
    {
        $elasticFixtures = static::getContainer()->get(ElasticsearchFixtures::class);
        $elasticFixtures->deleteTestFixtures();
    }

    protected function request(RequestToTest $request): ResponseInterface
    {
        $client = static::createClient();
        $data = ['headers' => $request->getHeaders()];
        if (
            \in_array($request->getMethod(), ['POST', 'PUT', 'PATCH'], true)
            || ('DELETE' == $request->getMethod() && $request->getData())
        ) {
            $data['json'] = $request->getData();
        }

        if ($request->getUser()) {
            $data['auth_bearer'] = $this->loginRest($client, $request->getUser());
        }

        return $client->request($request->getMethod(), $this->getRoute($request->getPath()), $data);
    }

    protected function validateApiCall(RequestToTest $request, ExpectedResponse $expectedResponse): ResponseInterface
    {
        $response = $this->request($request);
        $this->assertResponseStatusCodeSame($expectedResponse->getResponseCode());

        if (401 === $expectedResponse->getResponseCode()) {
            $this->assertJsonContains(
                [
                    'code' => 401,
                    'message' => 'JWT Token not found',
                ]
            );
        } elseif (405 === $expectedResponse->getResponseCode()) {
            $this->assertResponseStatusCodeSame($expectedResponse->getResponseCode());
        } elseif ($expectedResponse->getResponseCode() >= 400) {
            $errorType = 'hydra:Error';
            if (\array_key_exists('violations', $response->toArray(false))) {
                $errorType = 'ConstraintViolationList';
            }

            if ($expectedResponse->getMessage()) {
                $this->assertJsonContains(
                    [
                        '@type' => "$errorType",
                        'hydra:title' => 'An error occurred',
                        'hydra:description' => $expectedResponse->getMessage(),
                    ]
                );
            } else {
                $this->assertJsonContains(['@type' => "$errorType"]);
            }

            if ($expectedResponse->isValidateErrorResponse() && $expectedResponse->getValidateResponseCallback()) {
                $expectedResponse->getValidateResponseCallback()($response);
            }
        } elseif (204 != $expectedResponse->getResponseCode() && $expectedResponse->getValidateResponseCallback()) {
            $expectedResponse->getValidateResponseCallback()($response);
        } elseif (204 != $expectedResponse->getResponseCode()) {
            $data = $response->toArray();
            $this->assertArrayNotHasKey(
                'errors',
                $data,
                \array_key_exists('errors', $data)
                    ? (
                        \array_key_exists('debugMessage', $data['errors'][0])
                            ? $data['errors'][0]['debugMessage']
                            : $data['errors'][0]['message']
                    )
                    : ''
            );
        }

        return $response;
    }

    protected function assertGraphQlError(string $message): void
    {
        try {
            $this->assertJsonContains(['errors' => [['message' => $message]]]);
        } catch (ExpectationFailedException $e) {  // @phpstan-ignore-line
            if (!str_contains($e->getComparisonFailure()->getActualAsString(), '\'debugMessage\'')) {
                throw $e;
            }

            $this->assertJsonContains(['errors' => [['extensions' => ['debugMessage' => $message]]]]);
        }
    }

    protected function assertNoGraphQlError(array $responseData): void
    {
        $this->assertArrayNotHasKey(
            'errors',
            $responseData,
            \array_key_exists('errors', $responseData)
                ? (
                    \array_key_exists('debugMessage', $responseData['errors'][0])
                        ? $responseData['errors'][0]['debugMessage']
                        : $responseData['errors'][0]['message']
                )
                : ''
        );
    }

    protected function getApiRoutePrefix(): string
    {
        $routePrefix = trim(static::getContainer()->getParameter('route_prefix'), '/');

        return '/' . ($routePrefix ? $routePrefix . '/' : '');
    }

    protected function getRoute(string $route): string
    {
        return $this->getApiRoutePrefix() . trim($route, '/');
    }

    protected function getUri(string $shortName, string|int $id): string
    {
        return $this->getRoute(trim($shortName, '/') . '/' . $id);
    }
}
