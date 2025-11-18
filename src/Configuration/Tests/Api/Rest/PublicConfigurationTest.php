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

namespace Gally\Configuration\Tests\Api\Rest;

use Gally\Configuration\Tests\Api\PublicConfigurationGetCollectionTrait;
use Gally\Test\AbstractTestCase;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestToTest;
use Symfony\Contracts\HttpClient\ResponseInterface;

class PublicConfigurationTest extends AbstractTestCase
{
    use PublicConfigurationGetCollectionTrait;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::loadFixture([
            __DIR__ . '/../../fixtures/configurations.yaml',
            __DIR__ . '/../../fixtures/catalogs.yaml',
        ]);
    }

    /**
     * @dataProvider getFilteredCollectionDataProvider
     */
    public function testFilteredGetCollection(
        ?string $language,
        ?string $localCode,
        ?string $requestType,
        ?string $localizedCatalogCode,
        ?int $pageSize,
        ?int $currentPage,
        int $expectedResponseCode,
        array $expectedConfigurations,
    ): void {
        $data = array_filter([
            'language' => $language,
            'localeCode' => $localCode,
            'requestType' => $requestType,
            'localizedCatalogCode' => $localizedCatalogCode,
            'pageSize' => $pageSize,
            'currentPage' => $currentPage,
        ]);
        $query = http_build_query($data);
        $this->validateApiCall(
            new RequestToTest('GET', 'public_configurations' . ($query ? '?' . $query : ''), null),
            new ExpectedResponse(
                $expectedResponseCode,
                function (ResponseInterface $response) use ($expectedConfigurations) {
                    $this->assertJsonContains(['hydra:member' => $expectedConfigurations]);
                }
            )
        );
    }
}
